<?php

namespace App\Services\Sftp;

use App\Models\SftpConnection;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use Illuminate\Support\Facades\Log;

class SftpService
{
    /**
     * Test an SFTP connection: authenticate + check write permission.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(SftpConnection $connection): array
    {
        try {
            $filesystem = $this->createFilesystem($connection);

            // Step 1: Verify read access by listing root
            try {
                $filesystem->listContents('.')->toArray();
            } catch (\Throwable $e) {
                $rootPath = $connection->default_remote_path ?: '/';
                $detail = $e->getMessage();
                if ($prev = $e->getPrevious()) {
                    $detail .= ' | Cause: ' . $prev->getMessage();
                }
                Log::error('SFTP test: read failed', [
                    'connection_id' => $connection->id,
                    'root' => $rootPath,
                    'error' => $detail,
                    'exception_class' => get_class($e),
                ]);

                $message = "Cannot access remote folder '{$rootPath}'. It may not exist or the user lacks read permissions. Detail: {$detail}";
                $connection->recordTestResult(false, $message);
                return ['success' => false, 'message' => $message];
            }

            // Step 2: Verify write access
            $testFile = 'sftp_test_' . uniqid();
            try {
                $filesystem->write($testFile, 'connection_test');
                $filesystem->delete($testFile);
            } catch (\Throwable $e) {
                $detail = $e->getMessage();
                if ($prev = $e->getPrevious()) {
                    $detail .= ' | Cause: ' . $prev->getMessage();
                }
                Log::error('SFTP test: write failed', [
                    'connection_id' => $connection->id,
                    'test_file' => $testFile,
                    'error' => $detail,
                    'exception_class' => get_class($e),
                ]);

                $message = "Connected but cannot write to folder. The SFTP user may lack write permission. Detail: {$detail}";
                $connection->recordTestResult(false, $message);
                return ['success' => false, 'message' => $message];
            }

            $connection->recordTestResult(true, 'Connection successful. Read and write access verified.');

            return [
                'success' => true,
                'message' => 'Connection successful. Read and write access verified.',
            ];
        } catch (\Throwable $e) {
            $detail = $e->getMessage();
            if ($prev = $e->getPrevious()) {
                $detail .= ' | Cause: ' . $prev->getMessage();
            }
            Log::error('SFTP test: connection failed', [
                'connection_id' => $connection->id,
                'host' => $connection->host,
                'error' => $detail,
                'exception_class' => get_class($e),
            ]);

            $message = $this->parseErrorMessage($e);
            $connection->recordTestResult(false, $message);

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * Upload a local file to an SFTP connection with retry logic.
     *
     * @return array{success: bool, message: string}
     */
    public function uploadFile(
        SftpConnection $connection,
        string $localPath,
        string $remotePath,
        int $maxRetries = 3
    ): array {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $filesystem = $this->createFilesystem($connection);
                $stream = fopen($localPath, 'r');

                if ($stream === false) {
                    throw new \RuntimeException("Cannot open local file: {$localPath}");
                }

                $filesystem->writeStream($remotePath, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }

                Log::info('SFTP upload successful', [
                    'connection_id' => $connection->id,
                    'remote_path' => $remotePath,
                    'attempt' => $attempt,
                ]);

                return [
                    'success' => true,
                    'message' => "File uploaded successfully to {$connection->host}:{$remotePath}",
                ];
            } catch (\Throwable $e) {
                $lastError = $e;

                if (is_resource($stream ?? null)) {
                    fclose($stream);
                }

                Log::warning('SFTP upload attempt failed', [
                    'connection_id' => $connection->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s
                    sleep(pow(2, $attempt - 1));
                }
            }
        }

        $message = $this->parseErrorMessage($lastError);

        return [
            'success' => false,
            'message' => "Upload failed after {$maxRetries} attempts: {$message}",
        ];
    }

    /**
     * Create a Flysystem SFTP adapter for the given connection.
     */
    public function createFilesystem(SftpConnection $connection): Filesystem
    {
        $providerArgs = [
            'host' => $connection->host,
            'username' => $connection->username,
            'port' => $connection->port,
        ];

        if ($connection->auth_type === 'private_key') {
            $providerArgs['privateKey'] = $connection->private_key;
        } else {
            $providerArgs['password'] = $connection->password;
        }

        $provider = SftpConnectionProvider::fromArray($providerArgs);

        $adapter = new SftpAdapter(
            connectionProvider: $provider,
            root: $connection->default_remote_path ?: '/',
        );

        return new Filesystem($adapter);
    }

    /**
     * Parse a throwable into a user-friendly error message.
     */
    private function parseErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'Connection refused')) {
            return 'Connection refused. Check host and port.';
        }
        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'Connection timed out. Check host and network.';
        }
        if (str_contains($message, 'Authentication') || str_contains($message, 'auth')) {
            return 'Authentication failed. Check username and credentials.';
        }
        if (str_contains($message, 'Permission denied')) {
            return 'Permission denied. Check write access to remote folder.';
        }
        if (str_contains($message, 'No such file')) {
            return 'Remote folder does not exist. Check the path.';
        }

        return $message;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationRecipientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('notification_recipients')->delete();
        
        \DB::table('notification_recipients')->insert(array (
            0 => 
            array (
                'id' => '35a0fed3-59a4-4556-8aa1-2799fabf2d47',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            1 => 
            array (
                'id' => '6979ec2c-106f-4580-84bc-39af3fadcaa2',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            2 => 
            array (
                'id' => '7f716bff-0acc-4347-90f4-ede6fa937326',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            3 => 
            array (
                'id' => 'adf1e197-e1fe-4d92-974e-985c0433cbd3',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            4 => 
            array (
                'id' => 'dfd17c66-029b-4488-91cf-5861f0812f4d',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:20:48',
            ),
            5 => 
            array (
                'id' => 'de09a6a3-db47-41d2-be41-7bda732ca7fa',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 1,
                'read_at' => '2026-05-20 17:21:12',
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:21:12',
            ),
            6 => 
            array (
                'id' => 'd67782a2-5e29-400f-947c-5eea86d5f92f',
                'notification_id' => '019e4667-7f9d-71b4-95a1-31ef0d33b645',
                'user_id' => 11,
                'read_at' => '2026-05-20 17:21:35',
                'created_at' => '2026-05-20 17:20:48',
                'updated_at' => '2026-05-20 17:21:35',
            ),
            7 => 
            array (
                'id' => '267a6eee-da38-4c44-a98d-72ff2d87a67d',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            8 => 
            array (
                'id' => '0122c5dd-0445-4bd3-abae-a8f38bff6cde',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            9 => 
            array (
                'id' => 'f11420de-c65f-4dcd-840a-d805b817a899',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            10 => 
            array (
                'id' => 'cf988895-d9b1-425c-9e0d-fac330519e41',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            11 => 
            array (
                'id' => '68d58157-2e2a-4c56-8241-df36486a7c36',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:35:30',
            ),
            12 => 
            array (
                'id' => '23738de3-cf94-42a7-b52b-0aabff249e8f',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 1,
                'read_at' => '2026-05-20 17:36:37',
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-20 17:36:37',
            ),
            13 => 
            array (
                'id' => 'ee8cd589-39d7-4a1f-8fbe-1ac6bde3568d',
                'notification_id' => '019e4674-f66d-7292-b3e1-e593b03db53f',
                'user_id' => 11,
                'read_at' => '2026-05-21 15:22:47',
                'created_at' => '2026-05-20 17:35:30',
                'updated_at' => '2026-05-21 15:22:47',
            ),
            14 => 
            array (
                'id' => 'a5b83bba-92f0-40d0-b506-50e390a04095',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            15 => 
            array (
                'id' => '509ef67b-dad2-4f0c-9d93-800e9e799231',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            16 => 
            array (
                'id' => 'd71a6fbd-7764-45db-88e9-5d5313b327ce',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            17 => 
            array (
                'id' => '8f29199a-c963-4320-b1e1-7e02530d6a8c',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            18 => 
            array (
                'id' => 'a16ea6cc-1347-4d7e-8bb5-6834fe7929a3',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            19 => 
            array (
                'id' => 'bffbcd57-4e8c-494d-88b7-dc5b79f3f05a',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 11,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:23:40',
            ),
            20 => 
            array (
                'id' => '7245e29b-0d9d-46e8-abdc-ac8066ea1547',
                'notification_id' => '019e4b22-9f0b-71af-a77b-f2e28f824a65',
                'user_id' => 1,
                'read_at' => '2026-05-21 15:30:52',
                'created_at' => '2026-05-21 15:23:40',
                'updated_at' => '2026-05-21 15:30:52',
            ),
            21 => 
            array (
                'id' => 'a4a71678-8b4e-491f-b904-de8b986b42ef',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            22 => 
            array (
                'id' => 'a057b54a-2732-4b32-bb00-a5ce8c8e18e1',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            23 => 
            array (
                'id' => 'dc41c6a2-2f66-42d1-bb80-d44e407d84c6',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            24 => 
            array (
                'id' => 'a2b2fd42-6dd3-47ba-b21a-0674e6e68774',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            25 => 
            array (
                'id' => 'a596ad19-94ca-451e-8167-56c571f02030',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            26 => 
            array (
                'id' => 'c5222bca-2bdc-4c43-95fb-49f647fe7d93',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 11,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:01',
            ),
            27 => 
            array (
                'id' => '97cf6908-e330-4746-ad04-ce6dc20129d4',
                'notification_id' => '019e4b29-594a-709f-b894-230e988d0656',
                'user_id' => 1,
                'read_at' => '2026-05-21 15:31:44',
                'created_at' => '2026-05-21 15:31:01',
                'updated_at' => '2026-05-21 15:31:44',
            ),
            28 => 
            array (
                'id' => '6d32ae0a-7a42-4c5f-83aa-36b062ab35b7',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            29 => 
            array (
                'id' => 'd0301ad6-1ff9-4be5-9e5f-5d1265f8719a',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            30 => 
            array (
                'id' => 'a49168a3-0c83-4309-afd6-5ef31433fee4',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            31 => 
            array (
                'id' => 'e96c22d5-7455-4a3a-be79-a4aaeffd1ca4',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            32 => 
            array (
                'id' => 'b008a01d-73f6-45bb-82c1-e1b3b11caef6',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            33 => 
            array (
                'id' => '2433ddc0-1194-4440-9134-5cd2010ca632',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 11,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:36:36',
            ),
            34 => 
            array (
                'id' => '4470f1ec-22a5-47b1-aeab-a2eab5bdf499',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            35 => 
            array (
                'id' => '02aae212-6ab4-490a-ad0c-28d12376abbb',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            36 => 
            array (
                'id' => 'b1aae996-d76b-4209-8f4a-65b3be364dd5',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            37 => 
            array (
                'id' => '681fd70d-9e52-4ef4-8e41-8c1cfc8fde89',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            38 => 
            array (
                'id' => 'f36d0fbe-bbb6-4613-8f41-7bdaa116fc65',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            39 => 
            array (
                'id' => '61830be8-d159-4a38-8241-94705e2c56d5',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 11,
                'read_at' => NULL,
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:42:27',
            ),
            40 => 
            array (
                'id' => '2829cf4b-0bf1-4343-9315-6e0233863197',
                'notification_id' => '019e4b2e-7548-7265-9e94-7382f543a2b7',
                'user_id' => 1,
                'read_at' => '2026-05-21 15:43:16',
                'created_at' => '2026-05-21 15:36:36',
                'updated_at' => '2026-05-21 15:43:16',
            ),
            41 => 
            array (
                'id' => 'c26b68a3-233f-4164-bda9-b026ebfbb03a',
                'notification_id' => '019e4b33-d06f-736d-86e0-3f06c5afbcbe',
                'user_id' => 1,
                'read_at' => '2026-05-21 15:43:16',
                'created_at' => '2026-05-21 15:42:27',
                'updated_at' => '2026-05-21 15:43:16',
            ),
            42 => 
            array (
                'id' => '2b2ae6d6-a961-401b-9136-098e0138549a',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 1,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            43 => 
            array (
                'id' => 'd7b82482-204d-4931-8b7f-4c5b2feaaa60',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 3,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            44 => 
            array (
                'id' => '058e1bf1-cc2a-44da-ad37-0f129472b987',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 6,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            45 => 
            array (
                'id' => '2ec29646-0ea0-4672-92d5-4ebf8342af94',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 7,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            46 => 
            array (
                'id' => '9c981add-ed9e-4b78-804b-34001bc556a1',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 9,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            47 => 
            array (
                'id' => 'f40fb42c-9684-4ce7-81c9-9c95819a8176',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 10,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
            48 => 
            array (
                'id' => '71922852-8f86-4a38-a3e7-10d93daf83c8',
                'notification_id' => '019e6f05-870d-7183-a9de-15c2cdd9087e',
                'user_id' => 11,
                'read_at' => NULL,
                'created_at' => '2026-05-28 14:38:13',
                'updated_at' => '2026-05-28 14:38:13',
            ),
        ));
        
        
    }
}
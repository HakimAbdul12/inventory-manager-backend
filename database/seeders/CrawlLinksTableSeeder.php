<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CrawlLinksTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('crawl_links')->delete();
        
        \DB::table('crawl_links')->insert(array (
            0 => 
            array (
                'id' => '019ce944-bdf5-71ab-a147-1647700b52ea',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:15:22',
            ),
            1 => 
            array (
                'id' => '019ce944-bdfd-7334-a719-78acb4d8fe48',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:15:22',
            ),
            2 => 
            array (
                'id' => '019ce944-be0d-70da-bb35-04ee2fb5b1c0',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:15:22',
            ),
            3 => 
            array (
                'id' => '019ce944-be1c-71cd-b027-d8b6b615ad5d',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-be1a-71cb-9f10-682c959188cb',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:15:22',
            ),
            4 => 
            array (
                'id' => '019ce944-be29-715d-bf02-c1137d06a972',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'anchor_text' => 'Discover More',
                'created_at' => '2026-03-13 22:15:22',
            ),
            5 => 
            array (
                'id' => '019ce944-be2f-7333-9bf2-4ce9827dd643',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'to_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'anchor_text' => 'Get in Touch',
                'created_at' => '2026-03-13 22:15:22',
            ),
            6 => 
            array (
                'id' => '019ce944-beff-73e0-b6b1-4c8de2cd2102',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'to_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:15:22',
            ),
            7 => 
            array (
                'id' => '019ce944-bf0a-7134-97fb-e7bcb3d46320',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'to_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:15:22',
            ),
            8 => 
            array (
                'id' => '019ce944-bf12-7238-a1b7-23f932b39fde',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'to_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:15:22',
            ),
            9 => 
            array (
                'id' => '019ce944-bf1b-7011-a86e-c3f61d7707b6',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'to_page_id' => '019ce944-be1a-71cb-9f10-682c959188cb',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:15:22',
            ),
            10 => 
            array (
                'id' => '019ce944-bfac-704d-925c-6916d7ae148c',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'to_page_id' => '019ce944-bbc2-7127-a328-53a9ae9d2768',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:15:22',
            ),
            11 => 
            array (
                'id' => '019ce944-bfb3-7288-886b-342471e29f16',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'to_page_id' => '019ce944-bdfb-7297-9b63-02e9db501d4d',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:15:22',
            ),
            12 => 
            array (
                'id' => '019ce944-bfba-71d4-85e6-632bdaf544ca',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'to_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:15:22',
            ),
            13 => 
            array (
                'id' => '019ce944-bfc0-7266-8604-048098f17041',
                'crawl_job_id' => '019ce944-bbad-72d8-bf3f-d0d513a061cc',
                'from_page_id' => '019ce944-be0a-714e-b637-5f8d9c29c419',
                'to_page_id' => '019ce944-be1a-71cb-9f10-682c959188cb',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:15:22',
            ),
            14 => 
            array (
                'id' => '019ce946-476a-7000-a6e2-bbadc4d8cbf4',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:17:03',
            ),
            15 => 
            array (
                'id' => '019ce946-4773-717a-b0a5-8f3b540c22d0',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:17:03',
            ),
            16 => 
            array (
                'id' => '019ce946-4780-7184-a4f4-5ccbbcc0b3e2',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:17:03',
            ),
            17 => 
            array (
                'id' => '019ce946-478e-73c9-81a5-52f98eb4457e',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-478c-716e-8005-2cd309ec1c64',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:17:03',
            ),
            18 => 
            array (
                'id' => '019ce946-479a-7129-a682-c00ef00a87b8',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'anchor_text' => 'Discover More',
                'created_at' => '2026-03-13 22:17:03',
            ),
            19 => 
            array (
                'id' => '019ce946-479f-728f-9605-14adb51799cd',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'to_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'anchor_text' => 'Get in Touch',
                'created_at' => '2026-03-13 22:17:03',
            ),
            20 => 
            array (
                'id' => '019ce946-483d-72be-b6f6-d2625e8859cd',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'to_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:17:03',
            ),
            21 => 
            array (
                'id' => '019ce946-4845-710c-801a-e938915a3bfd',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'to_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:17:03',
            ),
            22 => 
            array (
                'id' => '019ce946-484b-709b-84c0-957e7b789c15',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'to_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:17:03',
            ),
            23 => 
            array (
                'id' => '019ce946-4851-7089-9421-d291c4089b96',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'to_page_id' => '019ce946-478c-716e-8005-2cd309ec1c64',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:17:03',
            ),
            24 => 
            array (
                'id' => '019ce946-48b7-7269-81ac-41b0c66ed541',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'to_page_id' => '019ce946-3cbf-71d3-9e8c-33ba2f808cad',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:17:03',
            ),
            25 => 
            array (
                'id' => '019ce946-48bc-72c6-83fd-8abaed236783',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'to_page_id' => '019ce946-4770-72e5-830e-541d47f38e22',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:17:03',
            ),
            26 => 
            array (
                'id' => '019ce946-48c2-7126-b54f-b7395beb31ba',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'to_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:17:03',
            ),
            27 => 
            array (
                'id' => '019ce946-48c8-72d3-bf9b-f53f034970f2',
                'crawl_job_id' => '019ce946-3c88-7069-bf8c-485845c94a95',
                'from_page_id' => '019ce946-477e-70f6-9dcb-4bacb5809def',
                'to_page_id' => '019ce946-478c-716e-8005-2cd309ec1c64',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:17:03',
            ),
            28 => 
            array (
                'id' => '019ce94f-6da6-71dd-a450-1bf2419a6ac1',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:27:02',
            ),
            29 => 
            array (
                'id' => '019ce94f-6db0-7176-88ac-803e368f9252',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:27:02',
            ),
            30 => 
            array (
                'id' => '019ce94f-6dc3-7045-976a-e068d5dfc8f5',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:27:02',
            ),
            31 => 
            array (
                'id' => '019ce94f-6dd3-7066-a973-b9c9c12a431b',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:27:02',
            ),
            32 => 
            array (
                'id' => '019ce94f-6de1-7343-95c5-af7311fd2516',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'anchor_text' => 'Discover More',
                'created_at' => '2026-03-13 22:27:02',
            ),
            33 => 
            array (
                'id' => '019ce94f-6de9-71c1-9f1e-ff356e6495f9',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'to_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'anchor_text' => 'Get in Touch',
                'created_at' => '2026-03-13 22:27:02',
            ),
            34 => 
            array (
                'id' => '019ce94f-6ec8-7221-a1c7-97d853b61da1',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'to_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:27:02',
            ),
            35 => 
            array (
                'id' => '019ce94f-6ecd-714b-84f4-fd5ceadb2e03',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'to_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:27:02',
            ),
            36 => 
            array (
                'id' => '019ce94f-6ed2-71fe-93ce-1b2c902770eb',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'to_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:27:02',
            ),
            37 => 
            array (
                'id' => '019ce94f-6ed7-7045-a453-78bc4257e451',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'to_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:27:02',
            ),
            38 => 
            array (
                'id' => '019ce94f-6f44-71a4-ac22-8ebb714f506c',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'to_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:27:03',
            ),
            39 => 
            array (
                'id' => '019ce94f-6f4b-7306-aa5f-1ab5223d1b5b',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'to_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:27:03',
            ),
            40 => 
            array (
                'id' => '019ce94f-6f51-71cf-9543-cdaa79845cf3',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'to_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:27:03',
            ),
            41 => 
            array (
                'id' => '019ce94f-6f57-7360-9a1e-9f26c97df723',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'to_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:27:03',
            ),
            42 => 
            array (
                'id' => '019ce94f-6fb5-7233-aa1c-09109521e8d3',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'to_page_id' => '019ce94f-6869-718b-8147-46e36d2330f7',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-13 22:27:03',
            ),
            43 => 
            array (
                'id' => '019ce94f-6fbb-7173-ad18-b793731c9504',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'to_page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'anchor_text' => 'About',
                'created_at' => '2026-03-13 22:27:03',
            ),
            44 => 
            array (
                'id' => '019ce94f-6fc1-7193-80f9-180c77d12fc6',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'to_page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-13 22:27:03',
            ),
            45 => 
            array (
                'id' => '019ce94f-6fc6-71dd-bebc-15fd50a9fa7e',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'from_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'to_page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-13 22:27:03',
            ),
            46 => 
            array (
                'id' => '019ce9cd-0801-7128-9eea-9ca30c5512d9',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:14',
            ),
            47 => 
            array (
                'id' => '019ce9cd-0809-7164-9aa8-2aefe4d00c62',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:14',
            ),
            48 => 
            array (
                'id' => '019ce9cd-0817-7202-b110-7b94c27ee4dc',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:14',
            ),
            49 => 
            array (
                'id' => '019ce9cd-0831-71ce-93cf-359372782abe',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:14',
            ),
            50 => 
            array (
                'id' => '019ce9cd-083f-736f-8541-49506d262556',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:14',
            ),
            51 => 
            array (
                'id' => '019ce9cd-084c-70d0-afec-aea71f3617dc',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:14',
            ),
            52 => 
            array (
                'id' => '019ce9cd-0859-72eb-8764-a349a08aef1e',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:14',
            ),
            53 => 
            array (
                'id' => '019ce9cd-0870-7368-acc7-3e6bb8e6e425',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:14',
            ),
            54 => 
            array (
                'id' => '019ce9cd-087e-71c0-adf3-38c97e8cfb26',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:14',
            ),
            55 => 
            array (
                'id' => '019ce9cd-088a-710a-8e8f-85aa31a6d73a',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:14',
            ),
            56 => 
            array (
                'id' => '019ce9cd-0894-732f-9dda-4286eefe71de',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Discover Services',
                'created_at' => '2026-03-14 00:44:14',
            ),
            57 => 
            array (
                'id' => '019ce9cd-0899-71c0-b5f8-1502e8a516ad',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About Us',
                'created_at' => '2026-03-14 00:44:14',
            ),
            58 => 
            array (
                'id' => '019ce9cd-0932-7035-a52e-f88212d75d82',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:14',
            ),
            59 => 
            array (
                'id' => '019ce9cd-0938-7014-99b9-5c8cfcd0bd9b',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:14',
            ),
            60 => 
            array (
                'id' => '019ce9cd-094a-7014-8a98-2b15a2237e4d',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:14',
            ),
            61 => 
            array (
                'id' => '019ce9cd-0950-7083-8068-e5476de3b049',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:14',
            ),
            62 => 
            array (
                'id' => '019ce9cd-0955-7162-88b9-d33af77a0df5',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:14',
            ),
            63 => 
            array (
                'id' => '019ce9cd-095b-7378-8483-52d0174b7bc3',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:14',
            ),
            64 => 
            array (
                'id' => '019ce9cd-0961-70c8-ae8f-cd06e3bc0f6f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:14',
            ),
            65 => 
            array (
                'id' => '019ce9cd-0967-71fd-b1b5-fd0a8186a1ea',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:14',
            ),
            66 => 
            array (
                'id' => '019ce9cd-096d-7319-a047-dd6d1473c58e',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:14',
            ),
            67 => 
            array (
                'id' => '019ce9cd-0973-71fd-8e14-5a6739c55ebc',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:14',
            ),
            68 => 
            array (
                'id' => '019ce9cd-0979-7346-8490-0be3df1e002a',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Return Home →',
                'created_at' => '2026-03-14 00:44:14',
            ),
            69 => 
            array (
                'id' => '019ce9cd-09fc-7180-ac71-c66215453714',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:14',
            ),
            70 => 
            array (
                'id' => '019ce9cd-0a03-711f-b3a8-157a049913a6',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:14',
            ),
            71 => 
            array (
                'id' => '019ce9cd-0a09-738d-85c5-e111f5c2e493',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:14',
            ),
            72 => 
            array (
                'id' => '019ce9cd-0a1d-7326-94e9-a2fc6936f760',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:14',
            ),
            73 => 
            array (
                'id' => '019ce9cd-0a23-7043-9bef-987876d86a38',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:14',
            ),
            74 => 
            array (
                'id' => '019ce9cd-0a2a-72f8-9776-31cef7175d62',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:14',
            ),
            75 => 
            array (
                'id' => '019ce9cd-0a30-73a8-b4aa-5dc839760643',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:14',
            ),
            76 => 
            array (
                'id' => '019ce9cd-0a37-720f-aa6e-fad36e71f0ea',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:14',
            ),
            77 => 
            array (
                'id' => '019ce9cd-0a3e-707d-85cf-5cdb760360bb',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:14',
            ),
            78 => 
            array (
                'id' => '019ce9cd-0a46-70fe-81f6-b03e9f129ff1',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:14',
            ),
            79 => 
            array (
                'id' => '019ce9cd-0a4c-70c9-ae5d-cd047a46b05b',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'View Pricing',
                'created_at' => '2026-03-14 00:44:14',
            ),
            80 => 
            array (
                'id' => '019ce9cd-0ad0-7198-99c2-9c172d70b2cf',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:14',
            ),
            81 => 
            array (
                'id' => '019ce9cd-0ad6-701e-8c67-ee8a75ff89ea',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:14',
            ),
            82 => 
            array (
                'id' => '019ce9cd-0adc-72b1-a33c-5c38fecae578',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:14',
            ),
            83 => 
            array (
                'id' => '019ce9cd-0ae2-7294-ab13-5e7b1ec18a51',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:14',
            ),
            84 => 
            array (
                'id' => '019ce9cd-0ae8-70b2-bb85-cdb95e8d149e',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:14',
            ),
            85 => 
            array (
                'id' => '019ce9cd-0afb-737c-a3ca-ba6fd451f77c',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:14',
            ),
            86 => 
            array (
                'id' => '019ce9cd-0b00-7297-96e0-75854caf10e3',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:14',
            ),
            87 => 
            array (
                'id' => '019ce9cd-0b05-73d6-a503-3d5c93cae471',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:14',
            ),
            88 => 
            array (
                'id' => '019ce9cd-0b0a-704f-86fb-6be3df2fffc8',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:14',
            ),
            89 => 
            array (
                'id' => '019ce9cd-0b0f-739d-aa26-197385eb70eb',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:14',
            ),
            90 => 
            array (
                'id' => '019ce9cd-0b15-7166-ab33-82b0f7e7a53f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Get Started',
                'created_at' => '2026-03-14 00:44:14',
            ),
            91 => 
            array (
                'id' => '019ce9cd-0b25-7360-990d-e9e7ede6c844',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Choose Plan',
                'created_at' => '2026-03-14 00:44:15',
            ),
            92 => 
            array (
                'id' => '019ce9cd-0b33-7091-888e-bb5083034951',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact Sales',
                'created_at' => '2026-03-14 00:44:15',
            ),
            93 => 
            array (
                'id' => '019ce9cd-0bea-7302-85f4-30419aaab383',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:15',
            ),
            94 => 
            array (
                'id' => '019ce9cd-0bf0-733a-8b7e-377b590d47c1',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:15',
            ),
            95 => 
            array (
                'id' => '019ce9cd-0bf7-7274-a6dd-f849aa65d84c',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:15',
            ),
            96 => 
            array (
                'id' => '019ce9cd-0bfd-7089-8dbb-e2799f6a9c24',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:15',
            ),
            97 => 
            array (
                'id' => '019ce9cd-0c04-73d0-9631-c17884df5bde',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:15',
            ),
            98 => 
            array (
                'id' => '019ce9cd-0c0a-7083-8034-cc63f9d3f1c0',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:15',
            ),
            99 => 
            array (
                'id' => '019ce9cd-0c10-70cc-ba77-9fa54081c640',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:15',
            ),
            100 => 
            array (
                'id' => '019ce9cd-0c16-7044-975b-99e9828cb357',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:15',
            ),
            101 => 
            array (
                'id' => '019ce9cd-0c1c-737a-81b4-423b3b336778',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:15',
            ),
            102 => 
            array (
                'id' => '019ce9cd-0c2f-7247-9ccd-ec5aac1b5907',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:15',
            ),
            103 => 
            array (
                'id' => '019ce9cd-0c36-7093-bfd9-21096fd64008',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Read More →',
                'created_at' => '2026-03-14 00:44:15',
            ),
            104 => 
            array (
                'id' => '019ce9cd-0c3f-71d9-bb4b-feabcfc3862f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Read More →',
                'created_at' => '2026-03-14 00:44:15',
            ),
            105 => 
            array (
                'id' => '019ce9cd-0c45-73b5-a203-14096cf63d06',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Read More →',
                'created_at' => '2026-03-14 00:44:15',
            ),
            106 => 
            array (
                'id' => '019ce9cd-0cdb-7307-8dcb-7401c3a1eb8e',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:15',
            ),
            107 => 
            array (
                'id' => '019ce9cd-0ce9-7265-b141-5a4e99adb44a',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:15',
            ),
            108 => 
            array (
                'id' => '019ce9cd-0cf8-73a2-9844-14beed05f62a',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:15',
            ),
            109 => 
            array (
                'id' => '019ce9cd-0d06-70f6-a359-5aa2c631a96f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:15',
            ),
            110 => 
            array (
                'id' => '019ce9cd-0d13-737c-b9bf-55882a9cf22c',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:15',
            ),
            111 => 
            array (
                'id' => '019ce9cd-0d1f-7010-b957-fa4564dd3d33',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:15',
            ),
            112 => 
            array (
                'id' => '019ce9cd-0d2d-7322-a359-e1a74d475c89',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:15',
            ),
            113 => 
            array (
                'id' => '019ce9cd-0d46-7281-b8f4-66929221a462',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:15',
            ),
            114 => 
            array (
                'id' => '019ce9cd-0d56-72d2-9314-afd9181f0153',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:15',
            ),
            115 => 
            array (
                'id' => '019ce9cd-0d5d-713d-a89e-302e4c8d97e1',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:15',
            ),
            116 => 
            array (
                'id' => '019ce9cd-0dcd-7201-a182-d03744348759',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:15',
            ),
            117 => 
            array (
                'id' => '019ce9cd-0dd3-72a7-adce-2606d7127919',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:15',
            ),
            118 => 
            array (
                'id' => '019ce9cd-0dd9-72de-a13e-3cdf209bb976',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:15',
            ),
            119 => 
            array (
                'id' => '019ce9cd-0de0-73ea-b8b3-c0b42b61ddc7',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:15',
            ),
            120 => 
            array (
                'id' => '019ce9cd-0de7-73e0-8297-e1e87690bee9',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:15',
            ),
            121 => 
            array (
                'id' => '019ce9cd-0dee-702d-911f-a0f39bac0a40',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:15',
            ),
            122 => 
            array (
                'id' => '019ce9cd-0df5-7128-931a-3062194472fe',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:15',
            ),
            123 => 
            array (
                'id' => '019ce9cd-0dfb-7306-9b9f-0d4507c51611',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:15',
            ),
            124 => 
            array (
                'id' => '019ce9cd-0e0e-70db-8486-db7f376f7bcf',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:15',
            ),
            125 => 
            array (
                'id' => '019ce9cd-0e15-73c4-a65c-d54eebf986d7',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:15',
            ),
            126 => 
            array (
                'id' => '019ce9cd-0e80-7367-87d9-f318019e679d',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:15',
            ),
            127 => 
            array (
                'id' => '019ce9cd-0e86-720e-9b86-ccaef6186f70',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:15',
            ),
            128 => 
            array (
                'id' => '019ce9cd-0e8b-70e5-b7ee-6b4f157f8199',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:15',
            ),
            129 => 
            array (
                'id' => '019ce9cd-0e90-7299-a586-37eac42da793',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:15',
            ),
            130 => 
            array (
                'id' => '019ce9cd-0e95-7382-b2ca-571582f020b0',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:15',
            ),
            131 => 
            array (
                'id' => '019ce9cd-0e9b-705c-89e7-69475f97b80f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:15',
            ),
            132 => 
            array (
                'id' => '019ce9cd-0ea0-70f3-8f0e-5899b9c9154b',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:15',
            ),
            133 => 
            array (
                'id' => '019ce9cd-0ea5-701c-8b5c-9a62263b9474',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:15',
            ),
            134 => 
            array (
                'id' => '019ce9cd-0eb5-7319-8ed2-b9d92351a44d',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:15',
            ),
            135 => 
            array (
                'id' => '019ce9cd-0ebb-7315-a231-22ddc465346b',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:15',
            ),
            136 => 
            array (
                'id' => '019ce9cd-0ec1-712b-8633-87d01e3bcf06',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Apply Now',
                'created_at' => '2026-03-14 00:44:15',
            ),
            137 => 
            array (
                'id' => '019ce9cd-0f38-72d2-9447-3271a23e69a5',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Apply Now',
                'created_at' => '2026-03-14 00:44:16',
            ),
            138 => 
            array (
                'id' => '019ce9cd-0f3e-72cf-8dd0-a3efc99df726',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Apply Now',
                'created_at' => '2026-03-14 00:44:16',
            ),
            139 => 
            array (
                'id' => '019ce9cd-0f44-72df-b5f9-987d42d4d824',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Send us an open application!',
                'created_at' => '2026-03-14 00:44:16',
            ),
            140 => 
            array (
                'id' => '019ce9cd-100b-71b0-b409-ef4bde62a3b3',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:16',
            ),
            141 => 
            array (
                'id' => '019ce9cd-1017-70c6-887e-aa2221df0753',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:16',
            ),
            142 => 
            array (
                'id' => '019ce9cd-1022-7032-acdc-a9ed83066687',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:16',
            ),
            143 => 
            array (
                'id' => '019ce9cd-102d-71d1-a04c-ce0993714610',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:16',
            ),
            144 => 
            array (
                'id' => '019ce9cd-1039-7067-85dd-4333aff371f0',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:16',
            ),
            145 => 
            array (
                'id' => '019ce9cd-1046-709f-ae6f-72af859780fe',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:16',
            ),
            146 => 
            array (
                'id' => '019ce9cd-1053-71de-b8ed-02869a08e88c',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:16',
            ),
            147 => 
            array (
                'id' => '019ce9cd-105f-73b2-9559-7443f45710d4',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:16',
            ),
            148 => 
            array (
                'id' => '019ce9cd-106c-71b0-a105-e7508dce174f',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:16',
            ),
            149 => 
            array (
                'id' => '019ce9cd-1077-73dc-b273-a12a1490ca0e',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:16',
            ),
            150 => 
            array (
                'id' => '019ce9cd-110e-706e-b5ac-4c041a8b4596',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0405-71a2-b1d9-ead09a03c085',
                'anchor_text' => 'Home',
                'created_at' => '2026-03-14 00:44:16',
            ),
            151 => 
            array (
                'id' => '019ce9cd-1114-72dc-a193-3bf15825c173',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0807-7340-a5d9-8fa8971bdb00',
                'anchor_text' => 'About',
                'created_at' => '2026-03-14 00:44:16',
            ),
            152 => 
            array (
                'id' => '019ce9cd-111b-7310-8f91-54055e349007',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0815-71db-860c-051a93ef2d24',
                'anchor_text' => 'Services',
                'created_at' => '2026-03-14 00:44:16',
            ),
            153 => 
            array (
                'id' => '019ce9cd-1121-7369-96ac-a599bd65bd40',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0823-7360-9449-73428ce4c550',
                'anchor_text' => 'Pricing',
                'created_at' => '2026-03-14 00:44:16',
            ),
            154 => 
            array (
                'id' => '019ce9cd-1126-7057-a5f5-a68125916850',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-083c-73d9-bc06-e2a0f1052111',
                'anchor_text' => 'Blog',
                'created_at' => '2026-03-14 00:44:16',
            ),
            155 => 
            array (
                'id' => '019ce9cd-112c-72bd-a3e4-a70d8098cb5c',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-084a-71b7-8faf-72645fb72459',
                'anchor_text' => 'FAQ',
                'created_at' => '2026-03-14 00:44:16',
            ),
            156 => 
            array (
                'id' => '019ce9cd-1131-72f6-b708-4f1477d0c7ee',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0856-7372-b68c-896432c9323e',
                'anchor_text' => 'Team',
                'created_at' => '2026-03-14 00:44:16',
            ),
            157 => 
            array (
                'id' => '019ce9cd-1137-7163-afae-3f95fb4361d0',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-086e-71ca-bff6-5f442149f769',
                'anchor_text' => 'Careers',
                'created_at' => '2026-03-14 00:44:16',
            ),
            158 => 
            array (
                'id' => '019ce9cd-113d-728c-a2ed-e5f545e54a30',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-087b-72b1-b587-099b7b20451e',
                'anchor_text' => 'Contact',
                'created_at' => '2026-03-14 00:44:16',
            ),
            159 => 
            array (
                'id' => '019ce9cd-1143-71d5-891d-5d43c4502a6b',
                'crawl_job_id' => '019ce9cd-03f8-71c7-9117-efddaee80afb',
                'from_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'to_page_id' => '019ce9cd-0887-7357-834f-b76ecb61fc3d',
                'anchor_text' => 'Terms',
                'created_at' => '2026-03-14 00:44:16',
            ),
        ));
        
        
    }
}
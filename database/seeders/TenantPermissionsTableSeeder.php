<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('tenant_permissions')->delete();
        
        \DB::table('tenant_permissions')->insert(array (
            0 => 
            array (
                'id' => '019dbfb2-8cb0-713e-974d-f9bc7a79c09c',
                'key' => 'inventory.view',
                'label' => 'View Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            1 => 
            array (
                'id' => '019dbfb2-8cba-711f-a8fd-81aa4b99ff7d',
                'key' => 'inventory.create',
                'label' => 'Create Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            2 => 
            array (
                'id' => '019dbfb2-8cbd-7349-9ba3-afc5d0a05d55',
                'key' => 'inventory.edit',
                'label' => 'Edit Inventory Details',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            3 => 
            array (
                'id' => '019dbfb2-8cc0-7050-b763-c58712065dc8',
                'key' => 'inventory.delete',
                'label' => 'Delete Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            4 => 
            array (
                'id' => '019dbfb2-8cc3-706a-b3db-90293e4c5d08',
                'key' => 'inventory.publish',
                'label' => 'Publish/Unpublish',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            5 => 
            array (
                'id' => '019dbfb2-8cc6-72a1-9ce7-ca54dd2bf416',
                'key' => 'inventory.archive',
                'label' => 'Archive Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            6 => 
            array (
                'id' => '019dbfb2-8cc9-71f8-a4da-f66584573f3e',
                'key' => 'inventory.import',
                'label' => 'Import Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            7 => 
            array (
                'id' => '019dbfb2-8ccc-7385-8581-a998602e8e30',
                'key' => 'inventory.export',
                'label' => 'Export Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            8 => 
            array (
                'id' => '019dbfb2-8ccf-73bb-be9c-4c902c6c29da',
                'key' => 'inventory.transfer',
                'label' => 'Transfer Inventory',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            9 => 
            array (
                'id' => '019dbfb2-8cd3-710e-b2d1-6463cb8bc74f',
                'key' => 'inventory.image.upload',
                'label' => 'Upload Images',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            10 => 
            array (
                'id' => '019dbfb2-8cd5-7188-be89-98f28fe46b95',
                'key' => 'inventory.image.delete',
                'label' => 'Delete Images',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            11 => 
            array (
                'id' => '019dbfb2-8cd8-7339-b82b-ada77a6422df',
                'key' => 'inventory.image.set_primary',
                'label' => 'Set Primary Image',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            12 => 
            array (
                'id' => '019dbfb2-8cdc-7359-b688-2009438e4052',
                'key' => 'inventory.video.upload',
                'label' => 'Upload Videos',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            13 => 
            array (
                'id' => '019dbfb2-8cde-7118-8f46-8d5af5cc7e68',
                'key' => 'inventory.video.delete',
                'label' => 'Delete Videos',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            14 => 
            array (
                'id' => '019dbfb2-8ce2-7393-bb88-b60601af58cf',
                'key' => 'inventory.document.upload',
                'label' => 'Upload Documents',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            15 => 
            array (
                'id' => '019dbfb2-8ce5-71a4-b9c8-e419d83752a8',
                'key' => 'inventory.document.delete',
                'label' => 'Delete Documents',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            16 => 
            array (
                'id' => '019dbfb2-8ce8-7240-b2af-8d737cf06463',
                'key' => 'inventory.ai.generate',
                'label' => 'Trigger AI Generation',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            17 => 
            array (
                'id' => '019dbfb2-8ceb-70b3-838a-4cd9e2416937',
                'key' => 'inventory.ai.analyze',
                'label' => 'Run AI Analysis',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            18 => 
            array (
                'id' => '019dbfb2-8cee-71f7-85df-c1938e2baebd',
                'key' => 'inventory.ai.description',
                'label' => 'Generate AI Description',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            19 => 
            array (
                'id' => '019dbfb2-8cf1-7173-a4d3-e83d584bfb38',
                'key' => 'inventory.price.edit',
                'label' => 'Edit Pricing',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            20 => 
            array (
                'id' => '019dbfb2-8cf4-70b7-b38b-b105250c131e',
                'key' => 'inventory.price.history',
                'label' => 'View Price History',
                'description' => NULL,
                'category' => 'Inventory',
                'created_at' => '2026-04-24 13:34:02',
                'updated_at' => '2026-04-24 13:34:02',
                'type' => 'high',
            ),
            21 => 
            array (
                'id' => '019dbfb2-8cf7-7238-8b17-edee904c9d24',
                'key' => 'activity.view',
                'label' => 'View Activity Logs',
                'description' => NULL,
                'category' => 'Activity',
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
                'type' => 'high',
            ),
            22 => 
            array (
                'id' => '019dbfb2-8cfb-70f0-91c3-6b0e6cb41fdd',
                'key' => 'activity.view_all',
                'label' => 'View All User Activity',
                'description' => NULL,
                'category' => 'Activity',
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
                'type' => 'high',
            ),
            23 => 
            array (
                'id' => '019dbfb2-8cfe-72ac-8944-848477d8e128',
                'key' => 'workspace.settings',
                'label' => 'Manage Workspace Settings',
                'description' => NULL,
                'category' => 'Workspace',
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
                'type' => 'high',
            ),
            24 => 
            array (
                'id' => '019dbfb2-8d01-7005-8a18-59c7f776e8dd',
                'key' => 'workspace.members',
                'label' => 'Manage Members',
                'description' => NULL,
                'category' => 'Workspace',
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
                'type' => 'high',
            ),
            25 => 
            array (
                'id' => '019dbfb2-8d04-724d-bfdd-8dc7c466f6ee',
                'key' => 'workspace.roles',
                'label' => 'Manage Roles & Permissions',
                'description' => NULL,
                'category' => 'Workspace',
                'created_at' => '2026-04-24 13:34:03',
                'updated_at' => '2026-04-24 13:34:03',
                'type' => 'high',
            ),
            26 => 
            array (
                'id' => '019dd8e1-1aa5-7034-815b-e04cb0b7a3c4',
                'key' => 'system.manage_roles',
                'label' => 'Manage System Roles',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-04-29 10:55:24',
                'updated_at' => '2026-04-29 10:55:24',
                'type' => 'low',
            ),
            27 => 
            array (
                'id' => '019dd8e1-1aae-7232-8144-7f321b297468',
                'key' => 'system.manage_permissions',
                'label' => 'Manage System Permissions',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-04-29 10:55:24',
                'updated_at' => '2026-04-29 10:55:24',
                'type' => 'low',
            ),
            28 => 
            array (
                'id' => '019dd8e1-1ab0-72fc-95d6-9db0d25e6699',
                'key' => 'system.view_all_tenants',
                'label' => 'View All Tenants',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-04-29 10:55:24',
                'updated_at' => '2026-04-29 10:55:24',
                'type' => 'low',
            ),
            29 => 
            array (
                'id' => '019dd8e1-1ab3-737f-8e67-31bc3e379463',
                'key' => 'system.switch_tenant',
            'label' => 'Switch Tenant (Admin)',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-04-29 10:55:24',
                'updated_at' => '2026-04-29 10:55:24',
                'type' => 'low',
            ),
            30 => 
            array (
                'id' => '019df899-3933-7043-8d11-2360600b8428',
                'key' => 'workspace.invite',
                'label' => 'Invite New Members',
                'description' => NULL,
                'category' => 'Workspace',
                'created_at' => '2026-05-05 14:44:44',
                'updated_at' => '2026-05-05 14:44:44',
                'type' => 'high',
            ),
            31 => 
            array (
                'id' => '019e001b-e4d5-7329-ae16-955ce2796552',
                'key' => 'inventory.acquisition.view',
                'label' => 'View Acquisition Details',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            32 => 
            array (
                'id' => '019e001b-e4df-7230-8504-1343c30b6b9c',
                'key' => 'inventory.acquisition.edit',
                'label' => 'Edit Acquisition Details',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            33 => 
            array (
                'id' => '019e001b-e4e3-723b-a18a-ceba435ef83b',
                'key' => 'leads.view',
                'label' => 'View Vehicle Leads',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            34 => 
            array (
                'id' => '019e001b-e4e6-7396-833a-7f7cc38a2f1e',
                'key' => 'leads.manage',
                'label' => 'Manage Leads',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            35 => 
            array (
                'id' => '019e001b-e4ea-73e5-a427-239d7ea82fe9',
                'key' => 'deals.view',
                'label' => 'View Deals',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            36 => 
            array (
                'id' => '019e001b-e4ed-73eb-8194-a8aa59def9b1',
                'key' => 'deals.manage',
                'label' => 'Create & Edit Deals',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            37 => 
            array (
                'id' => '019e001b-e4f0-73d6-9ea8-371903e0178a',
                'key' => 'service.view',
                'label' => 'View Service & Recon',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            38 => 
            array (
                'id' => '019e001b-e4f3-711b-98a7-bd900f74ecbc',
                'key' => 'service.manage',
                'label' => 'Manage Service & Recon Tasks',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            39 => 
            array (
                'id' => '019e001b-e4f7-738b-90f1-4f5ee652823b',
                'key' => 'publishing.view',
                'label' => 'View Publishing Status',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            40 => 
            array (
                'id' => '019e001b-e4fd-73bd-bd08-ef89ae5421f4',
                'key' => 'publishing.manage',
                'label' => 'Manage External Publishing',
                'description' => NULL,
                'category' => 'Dealership',
                'created_at' => '2026-05-07 01:44:48',
                'updated_at' => '2026-05-07 01:44:48',
                'type' => 'high',
            ),
            41 => 
            array (
                'id' => '019e2425-4261-7263-8831-ff332ff3a11c',
                'key' => 'crm.leads.view',
                'label' => 'View Leads',
                'description' => 'View CRM Leads',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            42 => 
            array (
                'id' => '019e2425-4275-7168-b1ee-38d6f9c70bc9',
                'key' => 'crm.leads.create',
                'label' => 'Create Leads',
                'description' => 'Create CRM Leads',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            43 => 
            array (
                'id' => '019e2425-427c-7244-87ed-6abf7aeaa1f4',
                'key' => 'crm.leads.edit',
                'label' => 'Edit Leads',
                'description' => 'Edit CRM Leads',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            44 => 
            array (
                'id' => '019e2425-4282-73f5-a0d5-b4690ab89342',
                'key' => 'crm.leads.delete',
                'label' => 'Delete Leads',
                'description' => 'Delete CRM Leads',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            45 => 
            array (
                'id' => '019e2425-4289-724c-b741-35663ee50567',
                'key' => 'crm.leads.assign',
                'label' => 'Assign Leads',
                'description' => 'Assign CRM Leads',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            46 => 
            array (
                'id' => '019e2425-428f-7323-ad58-2442c9a98164',
                'key' => 'crm.deals.view',
                'label' => 'View Deals',
                'description' => 'View CRM Deals',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            47 => 
            array (
                'id' => '019e2425-4295-71bc-8167-781215a8385d',
                'key' => 'crm.deals.create',
                'label' => 'Create Deals',
                'description' => 'Create CRM Deals',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            48 => 
            array (
                'id' => '019e2425-429b-7002-af67-0512a6733c31',
                'key' => 'crm.deals.edit',
                'label' => 'Edit Deals',
                'description' => 'Edit CRM Deals',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            49 => 
            array (
                'id' => '019e2425-42a2-7320-b8d8-9c87742a708f',
                'key' => 'crm.appointments.view',
                'label' => 'View Appointments',
                'description' => 'View CRM Appointments',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            50 => 
            array (
                'id' => '019e2425-42ab-7313-9926-3014c1a3c9b2',
                'key' => 'crm.appointments.create',
                'label' => 'Create Appointments',
                'description' => 'Create CRM Appointments',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            51 => 
            array (
                'id' => '019e2425-42b3-71b5-8733-741b08c982fe',
                'key' => 'crm.appointments.edit',
                'label' => 'Edit Appointments',
                'description' => 'Edit CRM Appointments',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            52 => 
            array (
                'id' => '019e2425-42bf-72a4-9f4d-650668113ad9',
                'key' => 'crm.tasks.view',
                'label' => 'View Tasks',
                'description' => 'View CRM Tasks',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            53 => 
            array (
                'id' => '019e2425-42c8-71ce-baf6-524950770fee',
                'key' => 'crm.tasks.create',
                'label' => 'Create Tasks',
                'description' => 'Create CRM Tasks',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            54 => 
            array (
                'id' => '019e2425-42cf-7293-b721-bfebb609ac2d',
                'key' => 'crm.tasks.edit',
                'label' => 'Edit Tasks',
                'description' => 'Edit CRM Tasks',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            55 => 
            array (
                'id' => '019e2425-42d7-705c-9710-d7f63d1384b9',
                'key' => 'crm.communications.view',
                'label' => 'View Communications',
                'description' => 'View CRM Communications',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            56 => 
            array (
                'id' => '019e2425-42de-72b1-b8c5-311a8381ce46',
                'key' => 'crm.communications.create',
                'label' => 'Create Communications',
                'description' => 'Create CRM Communications',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            57 => 
            array (
                'id' => '019e2425-42e5-71dc-bedd-6e9d32fc4839',
                'key' => 'crm.workflows.manage',
                'label' => 'Manage Workflows',
                'description' => 'Manage CRM Workflows',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            58 => 
            array (
                'id' => '019e2425-42ed-724e-9223-84d48a669c95',
                'key' => 'crm.dashboard.view',
                'label' => 'View CRM Dashboard',
                'description' => 'View CRM Dashboard',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            59 => 
            array (
                'id' => '019e2425-42f4-7157-a375-a500731b5b07',
                'key' => 'crm.checkins.manage',
                'label' => 'Manage Check-ins',
                'description' => 'Manage CRM Check-ins',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            60 => 
            array (
                'id' => '019e2425-42fd-7094-8284-be0e4edd823e',
                'key' => 'crm.analytics.view',
                'label' => 'View CRM Analytics',
                'description' => 'View CRM Analytics',
                'category' => 'CRM',
                'created_at' => '2026-05-14 01:41:22',
                'updated_at' => '2026-05-14 01:42:25',
                'type' => 'high',
            ),
            61 => 
            array (
                'id' => 'db2f11bc-9a79-4c46-857b-087246be5e94',
                'key' => 'crm.credit_application.view',
                'label' => 'View Credit Applications',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            62 => 
            array (
                'id' => '4af4c549-920e-4e93-924c-e9d48d9125fe',
                'key' => 'crm.credit_application.create',
                'label' => 'Create Credit Applications',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            63 => 
            array (
                'id' => 'e7ec0d1c-e504-43bd-8173-281689194d42',
                'key' => 'crm.credit_application.edit',
                'label' => 'Edit Credit Applications',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            64 => 
            array (
                'id' => 'fe778690-e28d-4298-bd0e-887ccbdc5bdd',
                'key' => 'crm.credit_application.send',
                'label' => 'Send Credit Application Links',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            65 => 
            array (
                'id' => '798ff559-02e9-46e4-827a-4d859e4b92f7',
                'key' => 'crm.credit_application.reactivate',
                'label' => 'Reactivate Credit Applications',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            66 => 
            array (
                'id' => '24eab84d-424d-4690-a188-d37567816e4f',
                'key' => 'crm.credit_application.download',
                'label' => 'Download Credit Application PDF',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-18 05:18:10',
                'updated_at' => '2026-05-18 05:18:10',
                'type' => 'high',
            ),
            67 => 
            array (
                'id' => '019e401c-cb42-7318-877f-9ad96726f8e2',
                'key' => 'crm.templates.view',
                'label' => 'View Message Templates',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-19 12:01:29',
                'updated_at' => '2026-05-19 12:01:29',
                'type' => 'high',
            ),
            68 => 
            array (
                'id' => '019e401c-cb54-72b1-9b35-4dd738de9084',
                'key' => 'crm.templates.manage',
                'label' => 'Manage Message Templates',
                'description' => NULL,
                'category' => 'CRM',
                'created_at' => '2026-05-19 12:01:29',
                'updated_at' => '2026-05-19 12:01:29',
                'type' => 'high',
            ),
            69 => 
            array (
                'id' => '019e4a38-d64e-722c-b5a5-e361d3c772de',
                'key' => 'system.manage_users',
                'label' => 'Manage System Users',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-05-21 11:08:19',
                'updated_at' => '2026-05-21 11:08:19',
                'type' => 'low',
            ),
            70 => 
            array (
                'id' => '019e4a38-d661-731b-acc2-a6d23fde6abd',
                'key' => 'system.manage_messages',
                'label' => 'Manage System Messages',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-05-21 11:08:19',
                'updated_at' => '2026-05-21 11:08:19',
                'type' => 'low',
            ),
            71 => 
            array (
                'id' => '019e4a38-d668-72be-9d82-509620117178',
                'key' => 'system.manage_categories',
                'label' => 'Manage Product Categories',
                'description' => NULL,
                'category' => 'System',
                'created_at' => '2026-05-21 11:08:19',
                'updated_at' => '2026-05-21 11:08:19',
                'type' => 'low',
            ),
        ));
        
        
    }
}
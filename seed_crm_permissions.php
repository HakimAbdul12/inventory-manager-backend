<?php

$permissions = [
    ['key' => 'crm.leads.view', 'label' => 'View Leads', 'description' => 'View CRM Leads', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.leads.create', 'label' => 'Create Leads', 'description' => 'Create CRM Leads', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.leads.edit', 'label' => 'Edit Leads', 'description' => 'Edit CRM Leads', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.leads.delete', 'label' => 'Delete Leads', 'description' => 'Delete CRM Leads', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.leads.assign', 'label' => 'Assign Leads', 'description' => 'Assign CRM Leads', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.deals.view', 'label' => 'View Deals', 'description' => 'View CRM Deals', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.deals.create', 'label' => 'Create Deals', 'description' => 'Create CRM Deals', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.deals.edit', 'label' => 'Edit Deals', 'description' => 'Edit CRM Deals', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.appointments.view', 'label' => 'View Appointments', 'description' => 'View CRM Appointments', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.appointments.create', 'label' => 'Create Appointments', 'description' => 'Create CRM Appointments', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.appointments.edit', 'label' => 'Edit Appointments', 'description' => 'Edit CRM Appointments', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.tasks.view', 'label' => 'View Tasks', 'description' => 'View CRM Tasks', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.tasks.create', 'label' => 'Create Tasks', 'description' => 'Create CRM Tasks', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.tasks.edit', 'label' => 'Edit Tasks', 'description' => 'Edit CRM Tasks', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.communications.view', 'label' => 'View Communications', 'description' => 'View CRM Communications', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.communications.create', 'label' => 'Create Communications', 'description' => 'Create CRM Communications', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.workflows.manage', 'label' => 'Manage Workflows', 'description' => 'Manage CRM Workflows', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.dashboard.view', 'label' => 'View Dashboard', 'description' => 'View CRM Dashboard', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.checkins.manage', 'label' => 'Manage Check-ins', 'description' => 'Manage CRM Check-ins', 'category' => 'crm', 'type' => 'low'],
    ['key' => 'crm.analytics.view', 'label' => 'View Analytics', 'description' => 'View CRM Analytics', 'category' => 'crm', 'type' => 'low'],
];

foreach ($permissions as $perm) {
    \App\Models\TenantPermission::updateOrCreate(
        ['key' => $perm['key']],
        $perm
    );
}

echo "Seeded CRM permissions successfully.\n";

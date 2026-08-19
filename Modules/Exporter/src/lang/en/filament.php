<?php

declare(strict_types=1);

return [
    'navigation' => [
        'group' => 'Exporters',
        'roles' => 'Exporter Roles',
        'profiles' => 'Exporter Profiles',
    ],

    'resources' => [
        'role' => [
            'label' => 'Exporter Role',
            'plural_label' => 'Exporter Roles',

            'sections' => [
                'general' => 'General Information',
                'restrictions' => 'Access Restrictions',
                'validity' => 'Validity Period',
            ],

            'fields' => [
                'name' => 'Role Name',
                'description' => 'Description',
                'is_unlimited' => 'Unlimited Access',
                'is_unlimited_help' => 'If enabled, this role has access to all regions and crops',
                'is_active' => 'Active',
                'regions' => 'Allowed Regions',
                'regions_placeholder' => 'Select regions (leave empty for all)',
                'regions_help' => 'Leave empty to allow all regions',
                'crops' => 'Allowed Crops',
                'crops_placeholder' => 'Select crops (leave empty for all)',
                'crops_help' => 'Leave empty to allow all crops',
                'starts_at' => 'Access Starts',
                'starts_at_help' => 'Leave empty for immediate access',
                'ends_at' => 'Access Ends',
                'ends_at_help' => 'Leave empty for indefinite access',
            ],

            'table' => [
                'name' => 'Name',
                'unlimited' => 'Unlimited',
                'regions' => 'Regions',
                'crops' => 'Crops',
                'validity' => 'Status',
                'assigned' => 'Assigned',
                'active' => 'Active',
                'created_at' => 'Created',
            ],

            'filters' => [
                'active' => 'Active',
                'unlimited' => 'Unlimited',
                'expired' => 'Expired',
            ],
        ],

        'profile' => [
            'label' => 'Exporter Profile',
            'plural_label' => 'Exporter Profiles',

            'sections' => [
                'company' => 'Company Information',
                'access_request' => 'Access Request',
                'access' => 'Access Control',
            ],

            'fields' => [
                'full_name' => 'Full Name',
                'position' => 'Position',
                'company_name' => 'Company Name',
                'license_number' => 'License Number',
                'inn' => 'INN (Tax ID)',
                'bio' => 'Bio',
                'role' => 'Access Role',
                'role_placeholder' => 'Select a role',
                'role_help' => 'Assign a role to control data access',
                'is_verified' => 'Verified',
                'verified_at' => 'Verified At',
                'verified_yes' => 'Yes',
                'verified_no' => 'No — use the Verify action',
                'access_request_status' => 'Access Status',
                'access_requested_at' => 'Requested At',
                'access_request_reason' => 'Request Reason',
            ],

            'table' => [
                'user' => 'User',
                'full_name' => 'Full Name',
                'company' => 'Company',
                'inn' => 'INN',
                'access_status' => 'Access',
                'requested_at' => 'Requested At',
                'role' => 'Role',
                'role_status' => 'Status',
                'verified' => 'Verified',
                'created_at' => 'Created',
            ],

            'filters' => [
                'access_status' => 'Access Status',
                'verified' => 'Verified',
                'role' => 'Role',
                'no_role' => 'No Role Assigned',
            ],

            'actions' => [
                'approve_access' => 'Approve',
                'reject_access' => 'Reject',
                'assign_role' => 'Assign Role',
                'verify' => 'Verify',
                'bulk_approve' => 'Approve Selected',
                'bulk_reject' => 'Reject Selected',
                'bulk_assign_role' => 'Assign Role to Selected',
                'bulk_verify' => 'Verify Selected',
            ],

            'notifications' => [
                'ineligible_for_unlimited_role' => 'Complete and verify this exporter profile (full name, company, INN, approved access request, verification) before assigning an unlimited-access role.',
                'bulk_assign_skipped' => ':count profile(s) skipped — incomplete or unverified for an unlimited-access role.',
            ],
        ],
    ],
];

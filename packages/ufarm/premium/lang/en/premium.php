<?php

return [
    'navigation' => [
        'group' => 'Premium',
        'label' => 'Premium Plans',
    ],

    'model' => [
        'label' => 'Premium Plan',
        'plural' => 'Premium Plans',
    ],

    'fields' => [
        'name' => 'Name',
        'currency' => 'Currency',
        'price' => 'Price',
        'duration_days' => 'Duration (days)',
        'application' => 'Application',
        'active' => 'Active',
        'application_helper' => 'Leave empty for universal plans available to all applications',
        'updated' => 'Updated',
        'subscribers' => 'Subscribers',
        'duration' => 'Duration',
        'universal' => 'Universal',
    ],

    'tabs' => [
        'all' => 'All Plans',
        'universal' => 'Universal',
    ],

    'filters' => [
        'active' => 'Active',
        'application' => 'Application',
        'universal' => 'Universal',
    ],

    'actions' => [
        'create' => 'Create Premium Plan',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],
];

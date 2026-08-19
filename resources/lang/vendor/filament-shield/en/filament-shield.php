<?php

// Files the sidebar's Roles entry under the panel's Administration group
// instead of Shield's own "Filament Shield" group (UFARM-2669). Shield reads
// its group from this key, so overriding it here is the only way to place the
// resource without patching the package.
return [
    'nav.group' => 'Administration',
];

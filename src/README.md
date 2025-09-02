# Burton for Craft CMS 5.x

This module provides a base theme for Craft CMS 5.x projects, particularly those built with the Burton protosite.

## Installation

### 1. Add entires to `modules` + `bootstrap` arrays in `config/app.php`:

```php
return [
    'modules' => [
        'burton' => [
            'class' => \modules\burton\Burton::class,
        ],
    ],
    'bootstrap' => ['burton'],
];
```

### 2. Add `psr-4` autoload record in `composer.json`:

```json
    "autoload": {
        "psr-4": {
          "modules\\burton\\": "modules/burton/src/"
        }
    },
```

### 3. Rebuild Composer autoload map:

```bash
    composer dump-autoload
```
# Commands

## Add dummy data

```sh
php bin/console app:insert-dummy-data
```

> **Note:** This could cause errors if there are modifications in the entities. Ensure to update the files in the commands accordingly.

## Add Balance

```sh
php bin/console app:add-balance <email> <amount>
```

## Update bet session status

```sh
php bin/console app:update-bet-session-status
```

## Make User Role

To assign a specific role to a user, use the following command:

```sh
php bin/console app:make-user-role <email> --role=<role>
```

This command will add the specified role (admin, seller, author) to the user. If the user already has the role, it will show a warning message.

### Example

To assign the admin role to a user with email `admin@admin.com`, use:

```sh
php bin/console app:make-user-role admin@admin.com --role=admin
```

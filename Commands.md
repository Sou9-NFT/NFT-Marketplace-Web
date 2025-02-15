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

## Make User Admin

To give a user admin role, use the following command:

```sh
php bin/console app:make-admin <email>
```

This command will add the ROLE_ADMIN role to the specified user. If the user already has the admin role, it will show a warning message.

# Commands

## Add dummy data

```sh
php bin/console app:insert-dummy-data
```

## Add Balance

```sh
php bin/console app:add-balance <email> <amount>
```

> **Note:** This could cause errors if there are modifications in the entities. Ensure to update the files in the commands accordingly.

## Update bet session status
```
php bin/console app:update-bet-session-status
```
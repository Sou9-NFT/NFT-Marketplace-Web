# Commands

## Add dummy data

```sh
php bin/console app:insert-dummy-data
```

> **Note:** This could cause errors if there are modifications in the entities. Ensure to update the files in the commands accordingly.
> 
## Add Balance

```sh
php bin/console app:add-balance <email> <amount>
```

## Update bet session status

```sh
php bin/console app:update-bet-session-status
```

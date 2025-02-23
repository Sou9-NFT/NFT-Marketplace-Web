# Commands

## Add dummy data

```sh
php bin/console app:insert-dummy-data
```

> **Note:** This could cause errors if there are modifications in the entities. Ensure to update the files in the commands accordingly.

## Add Balance (no longer works)

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

## Run Mercure

To run the Mercure service using Docker, use the following command:

```sh
docker run -d -p 3000:80 -e MERCURE_PUBLISHER_JWT_KEY='2vJXFuJ1Y0iaYgwYdZP4MD6OHyyyP/k3uGNcG0b2h7E=' -e MERCURE_SUBSCRIBER_JWT_KEY='2vJXFuJ1Y0iaYgwYdZP4MD6OHyyyP/k3uGNcG0b2h7E=' dunglas/mercure
```

This command will start the Mercure service in detached mode, mapping port 3000 on your host to port 80 on the container, and setting the necessary JWT keys for publisher and subscriber.
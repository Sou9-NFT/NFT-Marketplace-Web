# PULL FROM `beta2` BRANCH TO YOUR PRIVATE BRANCH BEFORE PUSHING

Download dependencies
```
composer install
```

How to start a server with Symfony
```
symfony server:start 
symfony serve
```

migration
```
symfony console make:migration
symfony console doctrine:migrations:migrate
```

clear cache
```
symfony console cache:clear
```

> [!WARNING]
> change the path of the assets in the twig files example:
> 
> `css/style.css` -> `front_office/css/style.css` OR `back_office/css/style.css`.

Database Name : `Sou9_NFT`

`DATABASE_URL="mysql://root:@127.0.0.1:3306/Sou9_NFT"`

## To check commands, see [Commands.md](./Commands.md)
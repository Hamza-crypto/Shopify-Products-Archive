# Shopify Orders Fetcher

A bot who will fetch orders from multiple stores and store into single database and also have api to access those.

Data fetching from orders is just title, price, created date.

## Installation
To install the app properly it is 4 phase process, lets discuss one by one:

### Phase 1 (Shopify Custom App Setup):

1. Visit [https://partners.shopify.com](https://partners.shopify.com)
2. Login with your shopify account on it
3. Go to Apps > Create App
4. Select Custom App, Add Urls

```
App URL = {host}/.../src  
Allowed redirection URL(s) =
{host}/.../src/generate_token.php
{host}/.../src/install.php
```

5. Copy `API key` and `API secret key`

### Phase 2 (Setup app and database on hosting):

1. Download zip folder from GitHub
2. Rename src/.env.backup to .env
3. Add all the credentials in .env file
4. Visit the url in browser `{HOST_URL}/src/install.php`

### Phase 3 (For Multiple Stores)

For multi stores, just copy store into store_1, store_2, store_3 and configure .env and cron jobs individually.

### Phase 4 (Cron job to run periodically)

Add the corn job for periodically run, it's totally upto how much often you want to run but I will recommend 15 minutes

```
php /path_to_your_app/src/orders.php
```

## Author
Mubashir Rasool Razvi (@rizimore)  
rizimore@outlook.com  
[https://www.upwork.com/freelancers/~01ef7b2184f920ecf7](https://www.upwork.com/freelancers/~01ef7b2184f920ecf7)

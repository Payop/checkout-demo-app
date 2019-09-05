Payop Checkout Demo Application
--

## Running on dev
```shell script
    php bin/console doctrine:database:create
```

## Checkout parameters:
* invoiceIdentifier - required
* payCurrency - required (can be taken from invoice)
* defaultCurrency - required (can be taken from invoice)
* customerData - required (can be taken from invoice)
* paymentMethod - required (can be taken from invoice)
* geoInformation - required (at least country required for several connectors). Maybe require to allow to add customer[country] to request
* customer - several fields required. Email required always
* cardToken - [required] if payment method from_type==cards




Docs: 
- https://developer.rbk.money/api/
- https://rbkmoney.github.io/webhooks-events-api/

## Run on DEV

Create `.env` file and put `ENVIRONMENT=dev` variable there. 
Then run:
```
    $ make up
```    

## Run on PROD

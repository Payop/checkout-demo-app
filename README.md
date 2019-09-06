Payop Checkout Demo Application
--

This is Payop Demo Application with Checkout integration.
This application describe integration using Server-To-Server api. 

### Please note!!!! 

**This is demo application. It's mostly describe only integration flow.
 We don't pay attention to code quality or errors handling.
 In the process of integrating your application, you must pay attention to error handling yourself.**

## Preconditions

1. Register account at [Payop.com](https://payop.com/),
 create ["project"](https://payop.com/en/profile/projects/create) and pass verification.
2. To use **Bank Card** payment methods using Server-To-Server integration
 your application (payop application) should have access to Card tokenization endpoint.
 To get this access, please [contact payop support](https://payop.com/en/profile/tickets/list)  


## Running on dev

* Clone application (`git clone`).
* Install Docker and Docker Compose.
* Go to the application directory and create config file.
 This application working under Docker and using environments variables for configuration.
    ```shell script
      cp .env.dist .env
    ``` 
* Setup configuration parameters according needs and your payop project setting.
 
    *Please note!!! Demo application require a few parameters
     which you can find in [Payop merchant admin panel](https://payop.com/en/profile/)*

* While you are finished with configuration, run docker compose
    ```shell script
      make up
    ```

That's all. Application should start and should be available using port from config file (*NGINX_PORT*).


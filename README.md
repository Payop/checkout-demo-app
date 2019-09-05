Payop Checkout Demo Application
--

This is Payop Demo Application with Checkout integration.
This application describe integration using Server-To-Server api. 

### Please note!!!! 

**This is demo application. It's mostly describe only integration flow.
 We don't pay attention to code quality or errors handling.
 In the process of integrating your application, you must pay attention to error handling yourself.**


## Running on dev

* Clone application (`git clone`).
* Install Docker and Docker Compose.
* Go to the application directory and create config file.
 Current application working under Docker and using environments variables for configuration.
    ```shell script
        cp .env.dist .env
    ``` 
* Setup configuration parameters according needs and your payop application setting.
 
    *Please note!!! Demo application require a few parameters which you can find in Payop merchant admin panel*

* While you are finished with configuration, run docker compose
    ```shell script
      make up
    ```

That's all. Application should start and should be available using port from config file (*NGINX_PORT*). 


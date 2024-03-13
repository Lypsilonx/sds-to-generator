# SETUP
1. Clone the repository
2. Create the following files (make sure they are not accessible from the web):
    1. webdavuser.config
    ```
    {
        "url" : "https://cloud.***/",
        "user" : "***"",
        "password" : "***"
    }
    ```
    2. Bot/chats.json
    ```
    {
         "groups": []
    }
    ```
    3. Bot/tokens.json
    ```
    []
    ```
    4. Bot/todelete.json
    ```
    []
    ```
    5. Bot/log.txt\
    Empty
3. Create a telegram bot and get the token
4. Paste the token in Bot/token.txt
5. Create a salt for the encryption and paste it in salt.txt
6. Set up a webhook to Bot/telegram.php\
https://api.telegram.org/botXXXX/setWebhook?url=XXX/Bot/telegram.php (add "&drop_pending_updates=true" to reset the bot when it gets stuck)
   


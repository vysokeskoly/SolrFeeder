VPN
===

    [
        USERNAME_PASSWORD
        ```
        github-connectvpn
        <password>
        ```
    
        + vsechny ostatni hodnoty
    ]
    |> List.map base64.encode
    |> List.iter save_to_github_secrets 


**Dulezite** je, ze USERNAME_PASSWORD promenna musi obsahovat `username` i `password` na samostatnych radcich

Vsechny hodnoty pro vpn pak musi byt encodovane pres base64. 
Pri buildu se nacpou do prislusnych souboru a pouzijou.

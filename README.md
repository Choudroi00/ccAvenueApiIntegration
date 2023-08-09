# ccAvenueApiIntegration

## BaseUrl :
 https://india-naa.in/api/v1/customer/payment/transaction



## Required parameters :
POST : 
| Paramenter | Description |
-------------|-------------
order_id | required*
amount | (not required just as confirmation)
language |(not required , default is EN)
currency | (not required , defaault is INR)

## POST proccess description :

  here in front-end a webview must be shown to allow user choose payment method , fill required payment info , and complete the process 

NOTE : the webview is required cuz it's all in one solution for different payment 
methods , without it fornt-end should include more UI views for available payment  methods 
also backend should include more sub-endpoints for each payment method 

## RESPONSE handling process :

the order status should be changes in case of success : unpaid ---> paid 
otherwise it will not changed from unpaid status .

TODO : recheck the order status from orders endpoint to check for ani changes , key : payment_status



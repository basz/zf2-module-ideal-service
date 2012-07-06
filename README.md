# iDealService

## Introduction
iDealService is module for Zend Framework 2 that aims to free shop owners from specific
acquirers 'lock-in'. It does this by having a unified interface on top of vendor specific
adapters.

It's aim is to make it simple to switch acquirer without the need to modify your business
code.

## Release information

dev-master

THIS RELEASE IS A DEVELOPMENT RELEASE AND NOT INTENDED FOR PRODUCTION USE.
PLEASE USE AT YOUR OWN RISK. REALLY!

updated to work with ZF2 beta-5

[![Build Status](https://secure.travis-ci.org/basz/zf2-module-ideal-service.png?branch=master)](http://travis-ci.org/basz/zf2-module-ideal-service)

## About iDEAL
[ideal](http://www.ideal.nl/ "iDEAL") is a standardized payment method for making secure online payments directly between
bank accounts. To offer iDEAL as a payment method in an online store, a direct link is
established with the systems of participating banks. In other words, this one connection
to iDEAL enables each webshop’s visitor with access to online banking of ABN AMRO,
ASN Bank, Friesland Bank, ING, Rabobank, RegioBank, SNS Bank, Triodos Bank or
Van Lanschot Bankiers to make payments in this way. No other payment product offers
this facility.

iDEAL is steadily gaining a reputation as a trusted online payment method. Already more
than half of all Dutch online shoppers use iDEAL.

## Supported Acquirers
  * Ing (Advanced) _broken_
  * Sisow (Rest) 
  * TargetPay _broken_

_Note: It will probably be a while before any new Acquirers are added. However I encourage you
to submit a pull request if you write a adapter for some Acquirer._

The acceptor: the owner of the online shop
The acquirer: the acceptor’s bank
The consumer: the customer who wants to buy a product from the acceptor’s online shop
The issuer: the consumer’s bank

## Installation
### Using Composer (recommended)
The recommended way to get a working copy of this project is to modify your composer.json
in your project root. This will take care of dependencies.

    "require":{
        "bushbaby/zf2-module-ideal-service":"1.0.*",
     },

and then update

    cd /to/your/project/directory
    php composer.phar update


## Putting iDealService to work
There are several actions you can perform. These actions are triggered by dispatching an
event to which this module will respond.

_todo_

## License
The MIT License (MIT)
Copyright (c) 2012 bushbaby multimedia

Permission is hereby granted, free of charge, to any person obtaining a copy of this
software and associated documentation files (the "Software"), to deal in the Software
without restriction, including without limitation the rights to use, copy, modify, merge,
publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or
substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

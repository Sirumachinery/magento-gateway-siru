# Magento 1 Siru Mobile Payment Gateway

This plugin provides mobile payments in Finland using Direct carrier billing through Siru Mobile.

## Features

* Supports mobile payments through any Finnish mobile subscription
* Detects automatically if user is using mobile internet
* Available in English and Finnish
* Test plugin against Siru Mobile sandbox API before going live

## Requirements

* Magento 1 (tested using 1.8.1)
* API credentials from Siru Mobile
* Payment gateway is only available in Finland and supports EUR as currency

## Installation

* Unpack the archive on your computer
* Copy the contents of Siru_Mobile/ folder to your Magento root folder
* Log in to your Magento admin section
* Browse to System > Cache management and clear all caches
* browse to System > Configuration > Payment Methods and configure Siru Mobile payment method

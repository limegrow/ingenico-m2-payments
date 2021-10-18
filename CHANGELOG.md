# Changelog

## [2.10.0] - 2021-11-14
## Changed
- Fixed: Undefined class constant 'CORE_CODE'
- Last_trans_id was empty, fix by persisting the payment
- Use `payIdSub` in `getPaymentInfo()`

## [2.9.3] - 2021-08-12
## Changed
- Fixed: Double invoicing
- Fixed: address fixes for Oney

## [2.9.2] - 2021-07-08
## Changed
- Fixed: Add Street number for Klarna payment methods only

## [2.9.1] - 2021-06-29
### Added
- Order status for "authorized or sale payments" per payment method

## Changed
- Change default status and state for reminder cron from new and pending to pending_payment
- Fixed: sales_order_save_commit_after event fired twice
- Fixed: Street number is sent twice
- Order status changes
- Buyer is stack on the Checkout/Review&Payments page when the "Cancel" button submitted on the Credit Cards -> Input card data
- Improve CleanExpiredOrders
- Added product in the "Сart" should not be cleared when the user returning to the store from the selected payment method like "Klarna Pay Now" or "AfterPay"
- Strange behaviour identified when the user automatically logout in case of emulation of "Uncertain" or "Refused" action to cancel payment.
- "Cart" cleanup when the user submits emulation of "Failues" or "Cancelled" action like for "Belfius" or "IngHomePay" payment methods.
- Added Product in the "Cart" should not be cleared whtn the user go "Back" from the Payment page or "Canceling" the payment.

## [2.9.0] - 2021-06-12
## Changed
- Fixed: Date of birth problem
- Fixed: "Access denied." occurred during created alias.
- Allow to use any countries for payments
- Fixed Bank Transfer title
- Excluded some payment methods for Generic method
- Fix Klarna parameters  
- Substitute street number from address
- Branding fixes
- Disable refunds for Intersolve

## [2.8.1] - 2021-05-27
### Added
- Klarna: Rounding workaround

### Changed
- Klarna parameters update
- Improved order cancellation code
- Klarna api updates + Order::isVirtual()
- Translation update
- Core library update  
- Code style formatting

## [2.8.0] - 2021-05-03
### Added
- Implemented Magento Commerce features

### Changes
- Remove "Open Invoice" title
- Trim `owneraddress`

## [2.7.0] - 2021-04-18
### Added
- Multi Store configuration
- New option: Layout of Payment Methods
- Clean config cache automatically on settings save
- Multishipping: "Remove" button which allows removing saved alias

### Changes
- iDeal bufixes
- Blank payment method bugfixes
- "Pay with Saved Card" bugfixes
- Update Klarna translations
- Multishipping: Fixed missing brand of CC on alias payment
- Require billing address for Bancontact

## [2.6.0] - 2021-03-25
### Added
- "Remove" button which allows removing saved alias
- Configurable logos for "Credit Cards"
- Bank selection for iDeal
- Blank payment methods

### Changed
- Klarna: Fix street field issues
- Klarna: Update format of date
- Fixed  discount bug
- Give preference to default status-state connections in `getAssignedState()`
- Small config value errors, causing the settings not to be properly stored

## [2.5.2] - 2020-11-01
### Added
- Added Sofort payment methods

### Changed
- Fixed "Store Credit" of Magento Commerce issues

## [2.5.1] - 2020-11-27
### Added
- Added Bancontact payment method

### Changed
- Fixed bug when payment methods have gone when generic method is disabled
- Updated translations
- Changed Invoice creating code
- Fixed locale of emails which should be sent to the admin

## [2.5.0] - 2020-11-03

### Changed
- Remove Klarna BankTransfer and Klarna Direct Debit
- Add possibility "Payment from Specific Countries" for Klarna methods

## [2.4.0] - 2020-10-26
### Added
- Payment methods on the checkout page
- Improved Credit Card payments using FlexCheckout
- Improved WhiteLabels feature

### Changed
- Add db index to ingenico_parent_order_id
- Fixed Alias table schema
- Fixed static compilation without db connection
- Fixed the missing fields validation of Klarna
- Fixed Mail order sending suppression
- Fixed refund processing status handling

## [2.3.0] - 2020-09-22
### Added
- Klarna Pay Later
- Klarna Pay Now
- Klarna Bank Transfer
- Klarna Direct Debit
- Klarna Financing
- Backward compatibility for Magento < 2.3
- Add "Pending payment" item to order statuses list
- Add payment info to admin backend, order view

### Changed
- Payment methods are available on the checkout page
- Credit Cards method can work in the inline mode on the checkout page
- Fixed validation of connection page on Multi Domain sites
- Improved refunds: partial and full
- Fixed Bank transfer double refund
- Fixed mail sending
- Use CN field for payments using saved aliases

## [2.2.1] - 2020-07-22
### Added
- Implemented additional workflows for Capture Processing:
	- For “capture funds” request, if response from payment gateway is not final then Invoice is created in “Pending” status, previously Invoice was created as “Paid” for any gateway response.
	- When transaction update is received from payment gateway via Webhook and status is final, then Invoice is marked as Paid and order is updated.
	- Gracefully handling situations where Invoices are created manually and “capture funds” request is issued using custom implementation in Merchant system. In such case only history record is added to Order.
- Implemented additional workflows for Refund Processing:
	- For “refund” request, if response from payment gateway is not final then Credit Memo is created in “Pending” status, previously Credit Memo was created as “Refunded” for any gateway response.
	- When transaction update is received from payment gateway via webhook and refund is confirmed, then Credit Memo is marked as “Refunded” and order is updated.
	- When transaction update is received from payment gateway via webhook and refund is refused, then notification emails are sent to Merchant and Customer notifying them of this event and no changes to Order are made.

### Changed
- Refactored how attachment is added to Support Emails and is now compatible with all Magento v2.x
- Additional settings for handling “Order Confirmation Email”. Now 3 modes are supported:
	- Allow Magento to send email when order is created using one of Ingenico Payment Methods
	- Restrict Magento to send email when order is created using one of Ingenico Payment Methods
	- Send email when order status is changed. This allows for more flexibility when adjusting for Merchant business logic.

## [2.1.0] - 2020-07-06
### Added
- Country support for Austria
- Settings to choose the desired order status for AUTHORIZATION and SALE

### Fixed
- Uploader error notice
- Code standards

## [2.0.0] - 2020-05-04
### Added
- Multishipping checkout
- Detailed transaction view

### Fixed
- db scheme fixes
- Code standards fixes
- Updated translations
- XSS in templates

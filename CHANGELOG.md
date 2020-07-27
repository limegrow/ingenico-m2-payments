# Changelog

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
- Bank transfer payment status change to be aligned with Ingenicos status when payment is pending.

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

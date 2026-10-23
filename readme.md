# Tagihan RT

[![Tests](https://github.com/ashadi-cc/tagihan-rt-public/actions/workflows/tests.yml/badge.svg)](https://github.com/ashadi-cc/tagihan-rt-public/actions/workflows/tests.yml)

A web app for managing an RT (neighbourhood association) in Indonesia: resident dues, payments, and the RT's finances. Several RTs can run on one installation, each as its own organization.

Administrators manage residents (warga), generate monthly and one-off bills (tagihan), and verify payments by bank transfer or QRIS, or from a resident's deposit. They also keep the RT cash book, financial reports, events, inventory, tenants and letters. Residents log in to see their bills, arrears and payment history.

A WhatsApp bot connects through the API in `routes/api.php`. Residents use it to register their number, reset their password and ask questions about their bills. Administrators use it to push announcements.

Built with Laravel 10, PHP 8.3 and MariaDB.

More detail is in [`docs/`](docs): the [bot API reference](docs/AI_FUNCTION_CALL_API.md) and notes on the [QRIS payment flow](docs/QRIS_PAYMENT_FLOW.md).

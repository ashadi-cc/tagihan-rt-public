# AI Function Call API Documentation

This document describes the API endpoints for AI function calls in the RT Tagihan application. These endpoints allow AI assistants to interact with the system to retrieve user information, billing data, payment history, transactions, inventory, and events.

## Table of Contents

1. [Authentication](#authentication)
2. [Base URL](#base-url)
3. [Endpoints](#endpoints)
   - [Get Current User](#get-current-user)
   - [Get Users](#get-users)
   - [Get User Bills](#get-user-bills)
   - [Get User Payments](#get-user-payments)
   - [Get Transactions](#get-transactions)
   - [Get Inventory Items](#get-inventory-items)
   - [Get Events](#get-events)
   - [Get Tenants](#get-tenants)
   - [Get User Details](#get-user-details)
4. [Response Format](#response-format)
5. [Error Handling](#error-handling)

---

## Authentication

All endpoints require authentication using Laravel Sanctum with the `admin:manage` ability.

**Headers Required:**
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

To obtain a token, you need to use the existing authentication mechanism in your application.

---

## Base URL

```
{APP_URL}/api/ai
```

Example: `https://yourdomain.com/api/ai`

---

## Endpoints

### Get Current User

Retrieve user information based on WhatsApp ID. This is typically used to identify who is asking the AI assistant.

**Endpoint:** `GET /api/ai/current-user`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| whatsapp_id | string | Yes | The WhatsApp ID of the user |

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/api/ai/current-user?whatsapp_id=6281234567890" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "username": "johndoe",
    "blok": "A-12",
    "blok_name": "A",
    "blok_number": "12",
    "phone_number": "081234567890",
    "whatsapp_id": "6281234567890",
    "jabatan": "Warga",
    "email": "john@example.com"
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "User not found"
}
```

---

### Get Users

Search for users by block (blok) and/or name. Returns limited user information (id, name, blok only).

**Endpoint:** `GET /api/ai/users`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| blok | string | No | Filter users by block (case-insensitive, partial match) |
| name | string | No | Filter users by name (case-insensitive, partial match) |

**Example Request:**
```bash
# Search by block
curl -X GET "https://yourdomain.com/api/ai/users?blok=A-12" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search by name
curl -X GET "https://yourdomain.com/api/ai/users?name=John" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search by both
curl -X GET "https://yourdomain.com/api/ai/users?blok=A&name=John" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "John Doe",
      "blok": "A-12"
    },
    {
      "id": 124,
      "name": "John Smith",
      "blok": "A-15"
    }
  ],
  "count": 2
}
```

**Notes:**
- Results are limited to 50 users maximum for performance
- Admin users are excluded from results
- If no parameters are provided, returns up to 50 users

---

### Get User Bills

Retrieve billing information for a specific user.

**Endpoint:** `GET /api/ai/bills`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | The ID of the user |
| billing_generated_date | date | No | Filter by billing generation date (YYYY-MM-DD) |
| limit | integer | No | Maximum number of results (1-100, default: 20) |

**Example Request:**
```bash
# Get all bills for a user
curl -X GET "https://yourdomain.com/api/ai/bills?user_id=123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get bills for a specific month
curl -X GET "https://yourdomain.com/api/ai/bills?user_id=123&billing_generated_date=2025-12-01" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Limit results
curl -X GET "https://yourdomain.com/api/ai/bills?user_id=123&limit=10" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "organization_id": 1,
      "billing_id": 5,
      "billing_name": "Iuran Bulanan",
      "billing_type": "monthly",
      "billing_amount": "100000.00",
      "billing_priority": 1,
      "billing_generated_date": "2025-12-01",
      "description": "Iuran bulanan Desember 2025",
      "payment_amount": "0.00",
      "user_id": 123,
      "user_name": "John Doe",
      "user_blok_number": "A-12",
      "created_by_user_id": 1,
      "created_by_user_name": "Admin",
      "created_at": "2025-12-01T00:00:00.000000Z",
      "updated_at": "2025-12-01T00:00:00.000000Z"
    }
  ],
  "count": 1,
  "summary": {
    "total_unpaid": 100000,
    "total_bills": 1
  }
}
```

**Notes:**
- Bills are ordered by date (newest first) and priority
- The summary includes total unpaid amount (billing_amount - payment_amount)

---

### Get User Payments

Retrieve payment history for a specific user.

**Endpoint:** `GET /api/ai/payments`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| user_id | integer | Yes | The ID of the user |
| status | string | No | Filter by status: `verified`, `pending`, or `rejected` |
| start_date | date | No | Filter payments from this date (YYYY-MM-DD) |
| end_date | date | No | Filter payments until this date (YYYY-MM-DD) |
| limit | integer | No | Maximum number of results (1-100, default: 20) |

**Example Request:**
```bash
# Get all payments for a user
curl -X GET "https://yourdomain.com/api/ai/payments?user_id=123" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get verified payments only
curl -X GET "https://yourdomain.com/api/ai/payments?user_id=123&status=verified" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get payments in a date range
curl -X GET "https://yourdomain.com/api/ai/payments?user_id=123&start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "organization_id": 1,
      "payment_id": "PAY-20251201-001",
      "payment_method": "transfer",
      "payment_name": "Pembayaran Iuran Bulanan",
      "payment_metadata": {
        "bank": "BCA",
        "account_number": "1234567890"
      },
      "payment_info_metadata": null,
      "payment_type": "monthly",
      "payment_type_metadata": "12-2025",
      "payment_amount": "100000.00",
      "payment_date": "2025-12-01",
      "user_id": 123,
      "user_name": "John Doe",
      "user_blok": "A-12",
      "created_by_user_id": 123,
      "created_by_user_name": "John Doe",
      "status": "verified",
      "verified_by_user_id": 1,
      "verified_by_user_name": "Admin",
      "verified_at": "2025-12-01T10:00:00.000000Z",
      "notes": null,
      "file_driver": "gcs",
      "file_path": "payments/proof-123.jpg",
      "is_sent_pushed": true,
      "created_at": "2025-12-01T09:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "count": 1,
  "summary": {
    "total_payments": 1,
    "total_amount": 100000,
    "verified_count": 1,
    "pending_count": 0,
    "rejected_count": 0
  }
}
```

**Notes:**
- Payments are ordered by payment date (newest first)
- The summary provides counts by status and total payment amount

---

### Get Transactions

Retrieve global RT transactions (income and expenses) for the organization.

**Endpoint:** `GET /api/ai/transactions`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | Filter by transaction type: `income` or `expense` |
| start_date | date | No | Filter transactions from this date (YYYY-MM-DD) |
| end_date | date | No | Filter transactions until this date (YYYY-MM-DD) |
| search | string | No | Search in name, description, or created_by fields |
| limit | integer | No | Maximum number of results (1-100, default: 50) |

**Example Request:**
```bash
# Get all transactions
curl -X GET "https://yourdomain.com/api/ai/transactions" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get only income transactions
curl -X GET "https://yourdomain.com/api/ai/transactions?type=income" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get transactions in a date range
curl -X GET "https://yourdomain.com/api/ai/transactions?start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search transactions
curl -X GET "https://yourdomain.com/api/ai/transactions?search=listrik" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "payment_date": "2025-12-01",
      "organization_id": 1,
      "billing_id": null,
      "name": "Pembayaran Listrik",
      "description": "Pembayaran listrik bulan Desember",
      "status": "verified",
      "file_driver": "gcs",
      "file_path": "rt-transactions/listrik-123.jpg",
      "debit": "500000.00",
      "credit": "0.00",
      "created_by": "Admin",
      "created_user_id": 1,
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z",
      "deleted_at": null
    },
    {
      "id": 2,
      "payment_date": "2025-12-01",
      "organization_id": 1,
      "billing_id": 5,
      "name": "Iuran Bulanan dari John Doe",
      "description": "Pembayaran iuran bulanan Desember",
      "status": "verified",
      "file_driver": null,
      "file_path": null,
      "debit": "0.00",
      "credit": "100000.00",
      "created_by": "system",
      "created_user_id": null,
      "created_at": "2025-12-01T09:00:00.000000Z",
      "updated_at": "2025-12-01T09:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "count": 2,
  "summary": {
    "total_income": 100000,
    "total_expense": 500000,
    "balance": -400000
  }
}
```

**Notes:**
- Transactions are ordered by payment date (newest first)
- Income transactions have `credit > 0` and `debit = 0`
- Expense transactions have `debit > 0` and `credit = 0`
- The summary reflects filtered data if date range is specified
- Balance = total_income - total_expense

---

### Get Inventory Items

Retrieve inventory items for the organization.

**Endpoint:** `GET /api/ai/inventory`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search in item_name, description, or stored_location |
| condition | string | No | Filter by item condition (e.g., "good", "damaged") |
| limit | integer | No | Maximum number of results (1-100, default: 50) |

**Example Request:**
```bash
# Get all inventory items
curl -X GET "https://yourdomain.com/api/ai/inventory" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search inventory
curl -X GET "https://yourdomain.com/api/ai/inventory?search=kursi" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Filter by condition
curl -X GET "https://yourdomain.com/api/ai/inventory?condition=good" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "organization_id": 1,
      "item_name": "Kursi Plastik",
      "quantity": 50,
      "condition": "good",
      "stored_location": "Gudang RT",
      "description": "Kursi plastik untuk acara RT",
      "file_driver": "gcs",
      "file_path": "inventory/kursi-plastik.jpg",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-12-01T00:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "count": 1
}
```

**Notes:**
- Items are ordered alphabetically by item_name
- Search performs partial match on item name, description, and location

---

### Get Events

Retrieve events for the organization.

**Endpoint:** `GET /api/ai/events`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| start_date | date | No | Filter events from this date (YYYY-MM-DD) |
| end_date | date | No | Filter events until this date (YYYY-MM-DD) |
| search | string | No | Search in title, description, or location |
| upcoming | boolean | No | If true, only show events from today onwards |
| limit | integer | No | Maximum number of results (1-100, default: 20) |

**Example Request:**
```bash
# Get all events
curl -X GET "https://yourdomain.com/api/ai/events" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get upcoming events only
curl -X GET "https://yourdomain.com/api/ai/events?upcoming=true" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Get events in a date range
curl -X GET "https://yourdomain.com/api/ai/events?start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search events
curl -X GET "https://yourdomain.com/api/ai/events?search=kerja bakti" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "organization_id": 1,
      "title": "Kerja Bakti Bulanan",
      "description": "Kerja bakti membersihkan lingkungan RT",
      "event_date": "2025-12-25",
      "location": "Lingkungan RT 01",
      "created_at": "2025-12-01T00:00:00.000000Z",
      "updated_at": "2025-12-01T00:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "count": 1
}
```

**Notes:**
- By default, events are ordered by date (newest first)
- When `upcoming=true`, events are ordered by date (earliest first)
- Search performs partial match on title, description, and location

---

### Get Tenants

Retrieve tenants (daftar pengontrak) for the organization.

**Endpoint:** `GET /api/ai/tenants`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| search | string | No | Search in name, contact_info, address, or unit_number |
| unit_number | string | No | Filter by unit number |
| limit | integer | No | Maximum number of results (1-100, default: 50) |

**Example Request:**
```bash
# Get all tenants
curl -X GET "https://yourdomain.com/api/ai/tenants" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Search tenants
curl -X GET "https://yourdomain.com/api/ai/tenants?search=ahmad" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"

# Filter by unit number
curl -X GET "https://yourdomain.com/api/ai/tenants?unit_number=A-12" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "organization_id": 1,
      "name": "Ahmad Ramadhan",
      "contact_info": "081234567890",
      "address": "Jl. Mawar No. 12",
      "unit_number": "A-12",
      "description": "Pengontrak rumah blok A nomor 12",
      "file_driver": "gcs",
      "file_path": "tenants/contract-ahmad.pdf",
      "created_at": "2025-01-01T00:00:00.000000Z",
      "updated_at": "2025-12-01T00:00:00.000000Z",
      "deleted_at": null
    }
  ],
  "count": 1
}
```

**Notes:**
- Tenants are ordered alphabetically by name
- Search performs partial match on name, contact info, address, and unit number
- Tenant represents "pengontrak" (renters) in the RT area

---

### Get User Details

Retrieve detailed information about a user including their current month bills, total outstanding bills (tunggakan), and recent payment history. This endpoint is useful for providing comprehensive financial overview for a specific user.

**Endpoint:** `GET /api/ai/user-details`

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| whatsapp_id | string | Yes | The WhatsApp ID of the user |

**Example Request:**
```bash
curl -X GET "https://yourdomain.com/api/ai/user-details?whatsapp_id=6281234567890" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "name": "John Doe",
      "blok": "A-12",
      "current_month_tagihan": "150000.00",
      "tunggakan_total": "300000.00"
    },
    "recent_payments": [
      {
        "id": 45,
        "organization_id": 1,
        "payment_id": "PAY-20251115-045",
        "payment_method": "transfer",
        "payment_name": "Pembayaran Iuran Bulanan November",
        "payment_metadata": {
          "bank": "BCA",
          "account_number": "1234567890"
        },
        "payment_info_metadata": null,
        "payment_type": "monthly",
        "payment_type_metadata": "11-2025",
        "payment_amount": "150000.00",
        "payment_date": "2025-11-15",
        "user_id": 123,
        "user_name": "John Doe",
        "user_blok": "A-12",
        "created_by_user_id": 123,
        "created_by_user_name": "John Doe",
        "status": "verified",
        "verified_by_user_id": 1,
        "verified_by_user_name": "Admin",
        "verified_at": "2025-11-15T10:30:00.000000Z",
        "notes": null,
        "file_driver": "gcs",
        "file_path": "payments/proof-123-nov.jpg",
        "is_sent_pushed": true,
        "created_at": "2025-11-15T09:00:00.000000Z",
        "updated_at": "2025-11-15T10:30:00.000000Z",
        "deleted_at": null
      }
    ]
  }
}
```

**Error Response (404):**
```json
{
  "success": false,
  "message": "User not found"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "whatsapp_id": ["The whatsapp id field is required."]
  }
}
```

**Notes:**
- `current_month_tagihan`: Total bills for the current month
- `tunggakan_total`: Total outstanding/unpaid bills across all periods
- `recent_payments`: Returns the 5 most recent payments ordered by payment date (newest first)
- User data is scoped to the authenticated user's organization

---

## Response Format

All API responses follow a consistent format:

### Success Response
```json
{
  "success": true,
  "data": [...],
  "count": 10,
  "summary": {...}  // Optional, depends on endpoint
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

---

## Error Handling

### HTTP Status Codes

| Status Code | Description |
|-------------|-------------|
| 200 | Success |
| 401 | Unauthorized (invalid or missing token) |
| 404 | Resource not found |
| 422 | Validation error (invalid parameters) |
| 500 | Server error |

### Common Error Scenarios

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "user_id": ["The user id field is required."],
    "limit": ["The limit must be between 1 and 100."]
  }
}
```

**404 Not Found:**
```json
{
  "success": false,
  "message": "User not found"
}
```

---

## Security Considerations

1. **Authentication**: All endpoints require a valid Sanctum token with `admin:manage` ability
2. **Organization Scoping**: Data is automatically scoped to the authenticated user's organization
3. **Rate Limiting**: Consider implementing rate limiting in production
4. **Input Validation**: All inputs are validated before processing
5. **Result Limits**: Maximum result limits prevent excessive data transfer
6. **SQL Injection Protection**: Laravel's query builder provides automatic protection

---

## Performance Optimization

1. **Pagination**: Results are limited (default 20-50, max 100) to prevent large data transfers
2. **Selective Fields**: Some endpoints return only necessary fields (e.g., user search returns id, name, blok only)
3. **Indexed Queries**: Ensure database indexes exist on frequently queried fields:
   - `users.whatsapp_id`
   - `users.blok`
   - `tagihans.user_id`, `tagihans.billing_generated_date`
   - `pembayarans.user_id`, `pembayarans.payment_date`
   - `rt_transactions.organization_id`, `rt_transactions.payment_date`
   - `inventories.organization_id`
   - `events.organization_id`, `events.event_date`
   - `tenants.organization_id`, `tenants.unit_number`

4. **Query Optimization**: Use eager loading when needed to prevent N+1 queries

---

## Usage Examples for Node.js

### Basic Setup

```javascript
const axios = require('axios');

const API_BASE_URL = 'https://yourdomain.com/api/ai';
const API_TOKEN = 'your-sanctum-token';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Authorization': `Bearer ${API_TOKEN}`,
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});
```

### Get Current User

```javascript
async function getCurrentUser(whatsappId) {
  try {
    const response = await apiClient.get('/current-user', {
      params: { whatsapp_id: whatsappId }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching user:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getCurrentUser('6281234567890')
  .then(data => console.log('User:', data.data))
  .catch(err => console.error(err));
```

### Search Users

```javascript
async function searchUsers(blok = null, name = null) {
  try {
    const params = {};
    if (blok) params.blok = blok;
    if (name) params.name = name;
    
    const response = await apiClient.get('/users', { params });
    return response.data;
  } catch (error) {
    console.error('Error searching users:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
searchUsers('A-12', 'John')
  .then(data => console.log('Users found:', data.count, data.data))
  .catch(err => console.error(err));
```

### Get User Bills

```javascript
async function getUserBills(userId, date = null, limit = 20) {
  try {
    const params = { user_id: userId, limit };
    if (date) params.billing_generated_date = date;
    
    const response = await apiClient.get('/bills', { params });
    return response.data;
  } catch (error) {
    console.error('Error fetching bills:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getUserBills(123, '2025-12-01', 10)
  .then(data => console.log('Bills:', data.summary, data.data))
  .catch(err => console.error(err));
```

### Get Transactions with Filters

```javascript
async function getTransactions(filters = {}) {
  try {
    const response = await apiClient.get('/transactions', { params: filters });
    return response.data;
  } catch (error) {
    console.error('Error fetching transactions:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getTransactions({
  type: 'income',
  start_date: '2025-12-01',
  end_date: '2025-12-31',
  limit: 50
})
  .then(data => console.log('Summary:', data.summary, 'Transactions:', data.data))
  .catch(err => console.error(err));
```

### Get Upcoming Events

```javascript
async function getUpcomingEvents(limit = 20) {
  try {
    const response = await apiClient.get('/events', {
      params: { upcoming: true, limit }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching events:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getUpcomingEvents(10)
  .then(data => console.log('Upcoming events:', data.data))
  .catch(err => console.error(err));
```

### Get Tenants

```javascript
async function getTenants(search = null, unitNumber = null, limit = 50) {
  try {
    const params = { limit };
    if (search) params.search = search;
    if (unitNumber) params.unit_number = unitNumber;
    
    const response = await apiClient.get('/tenants', { params });
    return response.data;
  } catch (error) {
    console.error('Error fetching tenants:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getTenants('ahmad', null, 20)
  .then(data => console.log('Tenants found:', data.count, data.data))
  .catch(err => console.error(err));
```

### Get User Details

```javascript
async function getUserDetails(whatsappId) {
  try {
    const response = await apiClient.get('/user-details', {
      params: { whatsapp_id: whatsappId }
    });
    return response.data;
  } catch (error) {
    console.error('Error fetching user details:', error.response?.data || error.message);
    throw error;
  }
}

// Usage
getUserDetails('6281234567890')
  .then(data => {
    console.log('User:', data.data.user);
    console.log('Current month bills:', data.data.user.current_month_tagihan);
    console.log('Total outstanding:', data.data.user.tunggakan_total);
    console.log('Recent payments:', data.data.recent_payments.length);
  })
  .catch(err => console.error(err));
```

---

## Version History

- **v1.2** (December 2025): Added user-details endpoint
- **v1.1** (December 2025): Added tenants endpoint
- **v1.0** (December 2025): Initial release with 7 endpoints

---

## Support

For questions or issues regarding these APIs, please contact the development team or refer to the main application documentation.

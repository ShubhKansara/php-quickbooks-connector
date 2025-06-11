# Laravel QuickBooks Desktop Connector

A Laravel package for seamless integration and two-way sync between your Laravel application and QuickBooks Desktop via the QuickBooks Web Connector (QBWC).  
Supports syncing of Customers, Items, Invoices, and more, with robust job queueing, logging, and error handling.

---

## 🚀 Features

- **QuickBooks Desktop Integration** via SOAP (QBWC)
- **Job Queue** for push/pull sync (customers, items, invoices, etc.)
- **Sync Status Tracking** (pending, processing, completed, error)
- **Detailed Logging** (success, warnings, errors)
- **Session Management** for QBWC connections
- **Event-driven**: Easily hook into sync events for custom logic
- **Extensible**: Add your own entities or sync logic

---

## 🛠️ Installation

1. **Require the package** (from your Laravel app root):

    ```bash
    composer require shubhkansara/php-quickbooks-connector
    ```

2. **Publish migrations, config, and listeners:**

    ```bash
    php artisan vendor:publish --provider="ShubhKansara\PhpQuickbooksConnector\QuickBooksConnectorServiceProvider"
    ```

3. **Run migrations:**

    ```bash
    php artisan migrate
    ```

4. **Configure** your `.env` and `config/php-quickbooks.php`:

    ```
    QB_MODE=desktop
    QUICKBOOKS_USERNAME=quickbooks
    QUICKBOOKS_PASSWORD=your_password
    ```

---

## 🏗️ Architecture & How It Works

### 1. **Web Connector (QBWC) Setup**

- Install [QuickBooks Web Connector](https://quickbooks.intuit.com/learn-support/en-us/help-article/list-management/set-up-quickbooks-web-connector/QBWC_SETUP)
- Import the provided `.qwc` file (see `LaravelQuickBooks.qwc`) into QBWC, pointing to your app's `/public/qbwc` endpoint.

### 2. **Authentication**

- QBWC authenticates using credentials from your `.env`/config.
- On success, a session ticket is issued.

### 3. **Job Queueing**

- All sync operations (push/pull) are queued in the `qb_sync_queue` table.
- Each job tracks:
    - `entity_type` (e.g., Customer, Item)
    - `action` (add, update, query, etc.)
    - `payload` (data to push or criteria to pull)
    - `status` (pending, processing, completed, error)
    - `result` (JSON-encoded result or error)

### 4. **Sync Flow**

- **Push**: When you want to send data to QuickBooks (e.g., create invoice), a job is queued.
- **Pull**: When you want to fetch data from QuickBooks (e.g., get all customers/items), a query job is queued.
- The Web Connector polls your `/qbwc` endpoint, which processes jobs one by one.

### 5. **Response Handling**

- **AddRs**: Handles push responses (e.g., after adding an invoice).
- **QueryRs**: Handles pull responses (e.g., after fetching customers/items).  
  The found data (listing) is saved as JSON in the `result` column of the queue.

### 6. **Events**

- `QuickBooksEntitySynced`: Fired after each successful pull, with the entity and records.
- `QuickBooksLogEvent`: Fired for all log messages (info, warning, error).

---

## 📦 Usage

### **Sync Customers/Items**

Queue a pull job (example):

```php
use ShubhKansara\PhpQuickbooksConnector\Models\QbSyncQueue;

// Queue a customer sync
QbSyncQueue::create([
    'entity_type' => 'Customer',
    'entity_id'   => 0,
    'action'      => 'query',
    'payload'     => null,
    'priority'    => 10,
    'status'      => 'pending',
]);
```

The next time QBWC runs, it will fetch customers from QuickBooks and save the result as JSON in the `result` column.

### **Sync Invoices (Push)**

```php
QbSyncQueue::create([
    'entity_type' => 'Invoice',
    'entity_id'   => $localInvoiceId,
    'action'      => 'add',
    'payload'     => $invoiceData, // array or object, will be JSON encoded
    'priority'    => 10,
    'status'      => 'pending',
]);
```

### **Accessing Sync Results**

After a job is completed, the `result` column contains the JSON data (listing or response):

```php
$job = QbSyncQueue::find($jobId);
$data = json_decode($job->result, true);
```

---

## 🧩 Key Classes & Files

- **`QuickBooksWebService`**  
  Main SOAP handler for QBWC. Handles authentication, job dispatch, response parsing, and logging.

- **`SyncManager`**  
  Handles job queue logic: next job, mark as started/processed, etc.

- **`QuickBooksDesktopConnector`**  
  Generates QBXML for each entity/action and processes responses.

- **Events**  
  - `QuickBooksEntitySynced`: Fired after a successful pull (with all records).
  - `QuickBooksLogEvent`: Fired for all log messages.

- **Migrations**  
  - `qb_sync_queue`: Tracks all sync jobs and results.
  - `qb_sync_sessions`: Tracks QBWC sessions.
  - `qb_sync_logs`: Stores logs for all sync activity.

---

## 📝 Example: Custom Event Listener

You can listen for `QuickBooksEntitySynced` to process synced data:

```php
use ShubhKansara\PhpQuickbooksConnector\Events\QuickBooksEntitySynced;

Event::listen(QuickBooksEntitySynced::class, function ($event) {
    // $event->entity, $event->records
    // Save to your own tables, trigger notifications, etc.
});
```

---

## ⚙️ API Endpoints

- `/public/qbwc`  
  Main SOAP endpoint for QuickBooks Web Connector.

- You can add your own API endpoints for triggering syncs, checking status, etc.

---

## 🧠 In-Depth: What Each Method Does

- **authenticate**: Validates QBWC credentials and issues a session ticket.
- **sendRequestXML**: Sends the next QBXML job to QuickBooks (push or pull).
- **receiveResponseXML**: Handles QuickBooks responses, parses XML, saves results (including listings) as JSON in the queue.
- **connectionError / getLastError / closeConnection**: Handle connection lifecycle and errors.

---

## 🛡️ Security

- Only accessible with correct credentials (from QBWC).
- All jobs and results are tracked for auditing.

---

## 🧩 Extending

- Add new entity types by extending `QuickBooksDesktopConnector` with new QBXML generators and response processors.
- Listen for events to hook into the sync lifecycle.

---

## 📝 Notes

- All pulled data (e.g., customer/item listings) is saved as JSON in the `result` column of `qb_sync_queue`.
- You can retrieve and process this data as needed in your application.

---

## 👨‍💻 Author

Developed by [Shubh Kansara](mailto:shubh.k.kansara@gmail.com)

---

## 📄 License

MIT



php artisan make:migration create_qb_entity_actions_table  --path=packages/ShubhKansara/php-quickbooks-connector/database/migrations  --realpath

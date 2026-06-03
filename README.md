```

███╗   ███╗██╗██╗  ██╗ ██████╗ ██╗     
████╗ ████║██║╚██╗██╔╝██╔═══██╗██║     
██╔████╔██║██║ ╚███╔╝ ██║   ██║██║     
██║╚██╔╝██║██║ ██╔██╗ ██║▄▄ ██║██║     
██║ ╚═╝ ██║██║██╔╝ ██╗╚██████╔╝███████╗
╚═╝     ╚═╝╚═╝╚═╝  ╚═╝ ╚══▀▀═╝ ╚══════╝   
__  __
\ \/ /
 \  / 
 /  \ 
/_/\_\
       _           
 _ __ | |__  _ __  
| '_ \| '_ \| '_ \ 
| |_) | | | | |_) |
| .__/|_| |_| .__/ 
|_|         |_|    
                                       
// -- Powered by:

┏┓┏┓┳┓┳┏┓┳┏┳┓┓┏
┗┓┣ ┃┃┃┃ ┃ ┃ ┗┫
┗┛┗┛┛┗┻┗┛┻ ┻ ┗┛
               
// --> https://senicity.com
// --
```

# MixQL for PHP

A fluent PHP client library for the MixQL encryption server. This package provides an object-oriented interface for performing hashing, salting, encryption, and key generation operations through the MixQL query language.

## Installation

### Composer (recommended)

Add the repository and require the package in your `composer.json`:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/senicity/mixql-php" }
    ],
    "require": {
        "senicity/mixql": "^1.1"
    }
}
```

Then run:

```bash
composer update
```

### Manual Installation
```php
// Include the autoloader
include_once 'mixql-php/autoload.php';

// Or manually include the main class
include_once 'mixql-php/src/MixQL.php';
```

## Quick Start

```php
require_once 'vendor/autoload.php';

// Create a MixQL instance (defaults to localhost:7272)
$mixql = new MixQL();

// Execute a simple query
$result = $mixql->select('SHA1(:input)')
               ->bind(['hello'])
               ->execute();

echo $result; // Outputs the server response
```

## Configuration

### Connection Options
```php
// Custom connection settings
$mixql = new MixQL(
    ['timeout' => 30],  // Socket timeout in seconds
    'mixql.example.com', // Server host
    8080                // Server port
);
```

### Default Values
- **Host**: `'localhost'`
- **Port**: `7272`
- **Timeout**: `30` seconds

## Authentication

When the MixQL server has authentication enabled, use the `auth()` method:

```php
$mixql = new MixQL();

// Authenticate before executing queries
$result = $mixql->select('SHA1(:password)')
               ->bind(['mysecret123'])
               ->auth('admin', 'secret123')  // Add authentication
               ->execute();

echo $result;
```

**Note**: Call `auth()` as the last method before `execute()` to ensure proper query formatting.

## Query Examples

### 1. SELECT Queries (Hashing)

```php
// Basic SHA1 hash
$result = $mixql->select('SHA1(:input)')
               ->bind(['password123'])
               ->execute();

// MD5 hash with uppercase output
$result = $mixql->select('MD5(:data)')
               ->bind(['sensitive_data'])
               ->uppercase()
               ->execute();

// Complex nested functions
$result = $mixql->select('SHA1(CONCAT(:user, NOW()))')
               ->bind(['admin'])
               ->execute();

// Multiple parameters
$result = $mixql->select('SHA1(CONCAT(:a, :b))')
               ->bind(['foo', 'bar'])
               ->execute();

// Base64 encoding
$result = $mixql->select('BASE64_ENCODE(:text)')
               ->bind(['Hello World'])
               ->execute();
```

### 2. Encryption / Decryption

```php
// Encrypt a value (uses server's configured enc_key)
$encrypted = $mixql->select('ENC(:input)')
                   ->bind(['my secret data'])
                   ->execute();

// Decrypt a value (returns plaintext)
$decrypted = $mixql->select('DEC(:input)')
                   ->bind([$encrypted])
                   ->execute();

// Encrypt with a custom key (KEY clause)
$encrypted = $mixql->select('ENC(:input)')
                   ->bind(['my secret data'])
                   ->key('mysecretkey123')
                   ->execute();

// Decrypt with a custom key
$decrypted = $mixql->select('DEC(:input)')
                   ->bind([$encrypted])
                   ->key('mysecretkey123')
                   ->execute();

// Encrypt with SALT (layered encryption passes)
$encrypted = $mixql->select('ENC(:input)')
                   ->salt('saltkey1', 'saltkey2')
                   ->bind(['my secret data'])
                   ->execute();

// Decrypt with SALT
$decrypted = $mixql->select('DEC(:input)')
                   ->salt('saltkey1', 'saltkey2')
                   ->bind([$encrypted])
                   ->execute();

// Encrypt with PEPPER (interleaves between characters)
$encrypted = $mixql->select('ENC(:input)')
                   ->pepper('abc', 'xyz')
                   ->bind(['my secret data'])
                   ->execute();

// Decrypt with PEPPER
$decrypted = $mixql->select('DEC(:input)')
                   ->pepper('abc', 'xyz')
                   ->bind([$encrypted])
                   ->execute();

// Full: KEY + SALT + PEPPER combined
$encrypted = $mixql->select('ENC(:input)')
                   ->key('mykey')
                   ->salt('salt1', 'salt2')
                   ->pepper('pep1', 'pep2')
                   ->bind(['my secret data'])
                   ->execute();

// Decrypt with same KEY + SALT + PEPPER
$decrypted = $mixql->select('DEC(:input)')
                   ->key('mykey')
                   ->salt('salt1', 'salt2')
                   ->pepper('pep1', 'pep2')
                   ->bind([$encrypted])
                   ->execute();
```

### 3. CREATE SALT (Salt Generation)

```php
// Single salt (16 characters default)
$result = $mixql->createSalt()->execute();

// Multiple salts with custom length
$result = $mixql->createSalt()
               ->amount(5)     // Generate 5 salts
               ->length(32)    // 32 characters each
               ->execute();

// Salt with SHA-1 hashing
$result = $mixql->createSalt()
               ->sha()         // Apply SHA-1 hash
               ->execute();

// Combined options
$result = $mixql->createSalt()
               ->amount(10)
               ->length(24)
               ->sha()
               ->execute();
```

### 4. CREATE KEY (Key Generation)

```php
// Single encryption key
$result = $mixql->createKey()->execute();

// Multiple keys
$result = $mixql->createKey()
               ->amount(5)     // Generate 5 keys
               ->execute();
```

### 5. CREATE UUID (UUID Generation)

```php
// Generate a UUID
$result = $mixql->createUUID()->execute();
```

### 6. STORE Commands (Persistent Queries)

```php
// Store a query for reuse
$result = $mixql->select('SHA1(:password)')
               ->store('hash_password')  // Store as 'hash_password'
               ->execute();

// List all stored queries
$result = $mixql->storeList()->execute();

// View a stored query
$result = $mixql->storeSelect('hash_password')->execute();

// Execute a stored query with parameters
$result = $mixql->storeUse('hash_password')
               ->bind(['mysecret123'])
               ->execute();

// Delete a stored query
$result = $mixql->storeDelete('hash_password')->execute();
```

### 7. Raw Queries

```php
// Execute raw MixQL query
$result = $mixql->raw('SELECT MD5(:data) AS hash UPPERCASE')
               ->bind(['test_data'])
               ->execute();
```

## Response Formatting

```php
// Get response as JSON
$json = $mixql->select('SHA1(:input)')
             ->bind(['test'])
             ->execute()
             ->json();  // Returns JSON string

// Pretty-print JSON
$pretty = $mixql->select('SHA1(:input)')
               ->bind(['test'])
               ->execute()
               ->json()
               ->pretty();  // Pretty-printed JSON

// Get response as PHP array
$array = $mixql->select('SHA1(:input)')
              ->bind(['test'])
              ->execute()
              ->array();  // Returns PHP array

// Get raw query string
$query = $mixql->select('SHA1(:input)')
              ->bind(['test'])
              ->rawQuery();  // Returns: "SELECT SHA1(:input) AS hash\ntest"
```

## Error Handling

```php
try {
    $result = $mixql->select('INVALID_FUNCTION(:input)')
                   ->bind(['test'])
                   ->execute();
    
    if ($result == 'INVALID_INPUT' || $result == 'INVALID_QUERY') {
        echo "Query error: $result";
    }
} catch (Exception $e) {
    echo "Connection error: " . $e->getMessage();
}
```

## Complete Example with All Features

```php
require_once 'vendor/autoload.php';

// Configure connection
$mixql = new MixQL(
    ['timeout' => 60],
    'encryption.server.com',
    7272
);

// Store a query for password hashing
$mixql->select('SHA1(CONCAT(:password, :salt))')
     ->store('hash_password_with_salt')
     ->execute();

// Later, use the stored query with authentication
$hashed = $mixql->storeUse('hash_password_with_salt')
               ->bind(['myPassword', 'randomSalt123'])
               ->auth('app_user', 'app_password')
               ->execute()
               ->array();

print_r($hashed);

// Generate secure keys
$keys = $mixql->createKey()
             ->amount(3)
             ->execute()
             ->array();

print_r($keys);
```

## Testing

Run the test suite:

```bash
# Run all query tests
php bin/run.php Query

# Run basic tests
php bin/run.php Test

# Test with specific options
php bin/run.php Query --json --pretty --bind=test1,test2

# Test authentication
php bin/run.php Query --bind=secret123 --auth=admin:secret123
```

## Available Methods

### Query Types
- `raw(string $query)` - Execute raw MixQL query
- `select(string $expression)` - SELECT query with hash expression
- `createSalt()` - Generate random salt
- `createKey()` - Generate encryption key
- `createUUID()` - Generate UUID
- `storeList()` - List stored queries
- `storeSelect(string $name)` - View stored query
- `storeUse(string $name)` - Execute stored query
- `storeDelete(string $name)` - Delete stored query
- `auth(string $username, string $password)` - Add authentication

### Query Modifiers
- `amount(int $limit)` - Set result count limit
- `length(int $length)` - Set output length
- `sha()` - Apply SHA-1 hashing (for CREATE SALT)
- `uppercase()` - Convert output to uppercase
- `store(string $name)` - Store query with name
- `key(string $key)` - Set custom encryption key for ENC/DEC
- `salt(string ...$salts)` - Add SALT layers for ENC/DEC
- `pepper(string ...$peppers)` - Add PEPPER interleaving for ENC/DEC
- `bind(array $params)` - Bind parameters to placeholders

### Response Formatters
- `json()` - Convert response to JSON
- `pretty()` - Pretty-print JSON response
- `array()` - Convert response to PHP array
- `rawQuery()` - Get current query string

## Server Functions Reference

The MixQL server supports these functions in SELECT queries:

| Function | Description | Example |
|---|---|---|
| `SHA1(x)` | SHA-1 hash (hex) | `SHA1(:input)` |
| `MD5(x)` | MD5 hash (hex) | `MD5(:data)` |
| `BASE64_ENCODE(x)` | Base64 encode | `BASE64_ENCODE(:text)` |
| `CONCAT(a, b, ...)` | Concatenate values | `CONCAT(:a, :b)` |
| `NOW()` | Current Unix timestamp | `NOW()` |
| `ENC(x)` | AES-256-CBC encrypt | `ENC(:input)` |
| `DEC(x)` | AES-256-CBC decrypt | `DEC(:input)` |

## Architecture & Documentation

For detailed architecture and component documentation, see:
- [ARCHITECTURE.md](ARCHITECTURE.md) - System design and architecture
- [AGENTS.md](AGENTS.md) - Component/agent documentation
- [STRUCTURE.md](STRUCTURE.md) - File structure reference

## Requirements

- PHP 7.4+ (for typed properties, match expression)
- MixQL server running (default: localhost:7272)
- `fsockopen()` enabled for TCP sockets

## Security Notes

- **Authentication**: Use `auth()` method when server authentication is enabled
- **Network Security**: Always deploy MixQL server behind a firewall
- **Sensitive Data**: The PHP client doesn't store data locally - all processing happens on the server
- **Error Handling**: Check for server error responses like `INVALID_INPUT`, `INVALID_QUERY`

## License

Copyright ©2026, Senicity Ltd. See `NOTICE` for details.

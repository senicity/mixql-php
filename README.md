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

### 2. SHA-256 & SHA-512 Hashing

```php
// SHA-256 hash (recommended over SHA1)
$result = $mixql->sha256()
               ->bind(['password123'])
               ->execute();

// SHA-512 hash
$result = $mixql->sha512()
               ->bind(['password123'])
               ->execute();

// With nested expression
$result = $mixql->sha256('CONCAT(:a, :b)')
               ->bind(['foo', 'bar'])
               ->execute();

// Uppercase output
$result = $mixql->sha256()
               ->bind(['hello'])
               ->uppercase()
               ->execute();
```

### 3. HMAC-SHA256

```php
// HMAC keyed hash (returns 64-char hex)
$result = $mixql->hmac()
               ->bind(['mySecretKey', 'message to sign'])
               ->execute();

// With custom expressions
$result = $mixql->hmac(':secret', ':data')
               ->bind(['key123', 'payload'])
               ->execute();
```

### 4. Argon2 Password Hashing

```php
// Hash a password with Argon2id (recommended for password storage)
$hash = $mixql->argon2()
             ->bind(['mypassword123'])
             ->execute();

echo $hash; // Encoded Argon2 hash with embedded salt

// Verify a password against a stored hash
$result = $mixql->argon2Verify()
               ->bind([$storedHash, 'mypassword123'])
               ->execute();

echo $result; // "true" or "false"

// Chain with SHA256 pre-hashing
$hash = $mixql->argon2('SHA256(:input)')
             ->bind(['mypassword123'])
             ->execute();

// Verify chained hash (apply same inner function)
$result = $mixql->argon2Verify(':hash', 'SHA256(:password)')
               ->bind([$storedHash, 'mypassword123'])
               ->execute();
```

### 5. Encryption / Decryption

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

### 6. AES-256-GCM Encryption (Recommended)

```php
// GCM authenticated encryption (recommended over ENC)
$encrypted = $mixql->encGcm()
                   ->bind(['my secret data'])
                   ->execute();

// GCM decryption
$decrypted = $mixql->decGcm()
                   ->bind([$encrypted])
                   ->execute();

// GCM with custom key
$encrypted = $mixql->encGcm()
                   ->bind(['my secret data'])
                   ->key('mysecretkey123')
                   ->execute();

// GCM decrypt with custom key
$decrypted = $mixql->decGcm()
                   ->bind([$encrypted])
                   ->key('mysecretkey123')
                   ->execute();

// GCM with SALT (layered encryption)
$encrypted = $mixql->encGcm()
                   ->salt('salt1', 'salt2')
                   ->bind(['my secret data'])
                   ->execute();

// GCM with PEPPER (character interleaving)
$encrypted = $mixql->encGcm()
                   ->pepper('abc', 'xyz')
                   ->bind(['my secret data'])
                   ->execute();

// GCM full: KEY + SALT + PEPPER
$encrypted = $mixql->encGcm()
                   ->key('mykey')
                   ->salt('salt1', 'salt2')
                   ->pepper('pep1', 'pep2')
                   ->bind(['my secret data'])
                   ->execute();

// GCM decrypt with same KEY + SALT + PEPPER
$decrypted = $mixql->decGcm()
                   ->key('mykey')
                   ->salt('salt1', 'salt2')
                   ->pepper('pep1', 'pep2')
                   ->bind([$encrypted])
                   ->execute();
```

### 7. CREATE SALT (Salt Generation)

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

### 8. CREATE KEY (Key Generation)

```php
// Single encryption key
$result = $mixql->createKey()->execute();

// Multiple keys
$result = $mixql->createKey()
               ->amount(5)     // Generate 5 keys
               ->execute();
```

### 9. CREATE UUID (UUID Generation)

```php
// Generate a UUID
$result = $mixql->createUUID()->execute();
```

### 10. STORE Commands (Persistent Queries)

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

### 11. Raw Queries

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
# Run basic tests
php bin/run.php Test

# Run all query tests
php bin/run.php Query

# Test with specific options
php bin/run.php Query --json --pretty --bind=test1,test2

# SHA-256 hash
php bin/run.php Query SHA256 --bind=hello

# SHA-512 with uppercase
php bin/run.php Query SHA512 --bind=hello --uppercase

# HMAC
php bin/run.php Query Hmac --bind=mykey,mymessage

# Argon2 hash
php bin/run.php Query Argon2 --bind=password123

# Argon2 verify
php bin/run.php Query Argon2Verify --bind=hashvalue,password123

# GCM encrypt with key + salt
php bin/run.php Query EncGcm --bind=secret --key=mykey --salt=s1,s2

# GCM decrypt with auth
php bin/run.php Query DecGcm --bind=encrypted_data --auth=admin:secret123

# Create key
php bin/run.php Query CreateKey --amount=3

# Create salt with options
php bin/run.php Query CreateSalt --amount=5 --length=32 --sha

# Create UUID
php bin/run.php Query CreateUUID

# Raw query
php bin/run.php Query "SELECT MD5(:data) AS hash" --bind=test
```

## Available Methods

### Query Types
- `raw(string $query)` - Execute raw MixQL query
- `select(string $expression)` - SELECT query with hash expression
- `sha256(string $expr = ':input')` - SHA-256 hash
- `sha512(string $expr = ':input')` - SHA-512 hash
- `encGcm(string $expr = ':input')` - AES-256-GCM authenticated encrypt
- `decGcm(string $expr = ':input')` - AES-256-GCM authenticated decrypt
- `hmac(string $keyExpr = ':key', string $msgExpr = ':msg')` - HMAC-SHA256 keyed hash
- `argon2(string $expr = ':input')` - Argon2id password hash
- `argon2Verify(string $hashExpr = ':hash', string $passExpr = ':password')` - Verify Argon2 hash
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
| `SHA256(x)` | SHA-256 hash (hex, 64 chars) | `SHA256(:input)` |
| `SHA512(x)` | SHA-512 hash (hex, 128 chars) | `SHA512(:input)` |
| `SHA1(x)` | SHA-1 hash (hex) — legacy | `SHA1(:input)` |
| `MD5(x)` | MD5 hash (hex) — legacy | `MD5(:data)` |
| `BASE64_ENCODE(x)` | Base64 encode | `BASE64_ENCODE(:text)` |
| `CONCAT(a, b, ...)` | Concatenate values | `CONCAT(:a, :b)` |
| `NOW()` | Current Unix timestamp | `NOW()` |
| `ENC(x)` | AES-256-CBC encrypt | `ENC(:input)` |
| `DEC(x)` | AES-256-CBC decrypt | `DEC(:input)` |
| `ENC_GCM(x)` | AES-256-GCM authenticated encrypt | `ENC_GCM(:input)` |
| `DEC_GCM(x)` | AES-256-GCM authenticated decrypt | `DEC_GCM(:input)` |
| `HMAC(key, msg)` | HMAC-SHA256 keyed hash (hex, 64 chars) | `HMAC(:key, :msg)` |
| `ARGON2(x)` | Argon2id password hash | `ARGON2(:input)` |
| `ARGON2_VERIFY(hash, x)` | Verify Argon2 hash (true/false) | `ARGON2_VERIFY(:hash, :password)` |

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

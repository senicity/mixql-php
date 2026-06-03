# MixQL PHP Package — Agents & Components

This document describes the functional agents (components) that make up the MixQL PHP package. Each agent is a logical unit of responsibility within the codebase.

---

## 1. MixQL Facade Agent

**Source**: `src/MixQL.php`

**Role**: Main entry point and facade for the entire library. Provides a simple, fluent interface for constructing and executing MixQL queries.

**Responsibilities**:
- Initializes connection to MixQL server with configurable host, port, and options
- Delegates query building to Query Types Agent
- Manages query execution through Request Agent
- Handles response storage and formatting
- Provides `__toString()` magic method for easy string conversion

**Key Methods**:
- `__construct($options = ['timeout' => 30], $host = 'localhost', $port = 7272)`: Sets up connection parameters
- `execute()`: Sends current query to server and returns self for chaining
- `__toString()`: Returns response as string (enables `echo $mixql`)

**Configuration**:
- Default timeout: 30 seconds
- Default host: localhost
- Default port: 7272

**Interfaces**:
- Input: Connection parameters, query method calls
- Output: Self instance for chaining, response via `__toString()`

**Usage Example**:
```php
$mixql = new MixQL();
$result = $mixql->select('SHA1(:input)')->bind(['password'])->execute();
echo $result; // Outputs server response
```

---

## 2. Query Types Agent

**Source**: `src/Query/Types.php`

**Role**: Provides type-safe methods for constructing specific MixQL query types. Maps PHP method calls to MixQL query language syntax.

**Responsibilities**:
- Maintains current query state in `$this->query` property
- Translates PHP method calls to MixQL command strings
- Extends Query Extensions Agent for modifier methods
- Returns self for fluent chaining

**Query Methods**:

| Method | MixQL Command | Description |
|---|---|---|
| `raw(string $query)` | `<raw_query>` | Executes raw MixQL query string |
| `select(string $hash)` | `SELECT <hash> AS hash` | SELECT query with hash expression |
| `sha256(string $expr)` | `SELECT SHA256(<expr>) AS hash` | SHA-256 hash |
| `sha512(string $expr)` | `SELECT SHA512(<expr>) AS hash` | SHA-512 hash |
| `encGcm(string $expr)` | `SELECT ENC_GCM(<expr>) AS hash` | AES-256-GCM authenticated encrypt |
| `decGcm(string $expr)` | `SELECT DEC_GCM(<expr>) AS hash` | AES-256-GCM authenticated decrypt |
| `hmac(string $key, string $msg)` | `SELECT HMAC(<key>, <msg>) AS hash` | HMAC-SHA256 keyed hash |
| `argon2(string $expr)` | `SELECT ARGON2(<expr>) AS hash` | Argon2id password hash |
| `argon2Verify(string $hash, string $pass)` | `SELECT ARGON2_VERIFY(<hash>, <pass>) AS hash` | Verify Argon2 hash |
| `createSalt()` | `CREATE SALT` | Generates random salt |
| `createKey()` | `CREATE KEY` | Generates encryption key |
| `createUUID()` | `CREATE UUID` | Generates UUID |
| `storeList()` | `STORE LIST` | Lists stored queries |
| `storeDelete(string $name)` | `STORE DELETE <name>` | Deletes stored query |

**Query Building Flow**:
```php
// Internal state management
$this->query = MIXQL_SELECT . $hash . MIXQL_ASHASH;
return $this; // For chaining
```

**Interfaces**:
- Input: Method calls with parameters
- Output: Self instance with updated query state

---

## 3. Query Extensions Agent

**Source**: `src/Query/Extensions.php`

**Role**: Provides modifier methods for customizing and enhancing queries. Handles parameter binding and response formatting.

**Responsibilities**:
- Adds query modifiers (LIMIT, LENGTH, SHA, UPPERCASE, STORE AS)
- Binds parameters to named placeholders
- Formats server responses (JSON, array, pretty print)
- Parses special response formats (STORED QUERIES table)

**Modifier Methods**:

| Method | MixQL Modifier | Description |
|---|---|---|
| `amount(int $limit)` | `LIMIT <n>` | Sets result count limit |
| `length(int $length)` | `LENGTH <n>` | Sets output length |
| `sha()` | `SHA` | Applies SHA-1 hashing |
| `uppercase()` | `UPPERCASE` | Converts output to uppercase |
| `store(string $name)` | `STORE AS <name>` | Stores query with name |
| `bind(array $params)` | `\n<param1>\n<param2>` | Binds parameters to `:placeholder` |

**Response Formatting Methods**:

| Method | Description |
|---|---|
| `json()` | Converts response to JSON string |
| `pretty()` | Pretty-prints JSON response |
| `array()` | Converts response to PHP array |
| `rawQuery()` | Returns current query string |

**Special Parsing**:
- `parseRawList()`: Parses STORED QUERIES table format into structured array
- Handles multi-line responses and table parsing

**Parameter Binding**:
```php
public function bind(array $params): self
{
    foreach($params as $param){
        $this->query .= "\n" . $param;
    }
    return $this;
}
```

**Interfaces**:
- Input: Modifier calls, parameters, response data
- Output: Modified query, formatted responses

---

## 4. Request Agent

**Source**: `src/Send/Request.php`

**Role**: Manages the execution of queries by delegating to the Socket Agent. Acts as an abstraction layer between query building and socket communication.

**Responsibilities**:
- Maintains connection parameters (host, port, options)
- Provides `execute()` method for query execution
- Extends Socket Agent for actual communication
- Simple pass-through to socket layer

**Key Methods**:
- `__construct(string $host, int $port, array $options)`: Stores connection details
- `execute($query)`: Delegates to `send($query)` method

**Design Pattern**: Template Method - provides interface while delegating implementation.

**Interfaces**:
- Input: Query string
- Output: Raw server response

---

## 5. Socket Agent

**Source**: `src/Send/Socket/Socket.php`

**Role**: Handles low-level TCP socket communication with the MixQL server. Manages connection lifecycle, data transmission, and error handling.

**Responsibilities**:
- Establishes TCP connection using `fsockopen()`
- Sends query with proper newline formatting
- Reads response stream with timeout handling
- Manages connection errors and cleanup
- Ensures query termination with newline

**Core Method**: `send($query)`

**Process Flow**:
1. **Query Normalization**:
   ```php
   $query = str_replace("\\n", "\n", $query);
   $query = rtrim($query) . "\n";  // Ensure newline termination
   ```

2. **Connection Establishment**:
   ```php
   $socket = fsockopen($this->host, $this->port, $errno, $errstr, 10);
   ```

3. **Error Handling**:
   ```php
   if (!$socket) {
       echo "Error: $errstr ($errno)<br />\n";
       return false;
   }
   ```

4. **Data Transmission**:
   ```php
   fwrite($socket, $query);
   stream_set_timeout($socket, $this->options['timeout']);
   ```

5. **Response Reading**:
   ```php
   $response = '';
   while (!feof($socket)) {
       $line = fgets($socket, 1024); 
       if ($line) {
           $response .= $line;
       }
   }
   ```

6. **Cleanup**:
   ```php
   fclose($socket);
   return $response;
   ```

**Configuration**:
- Connection timeout: 10 seconds (connection phase)
- Read timeout: From `$this->options['timeout']` (default 30s)
- Buffer size: 1024 bytes per read

**Error Conditions**:
- Connection refused
- Host unreachable
- Timeout during read
- Socket errors

**Interfaces**:
- Input: Formatted query string
- Output: Raw server response or `false` on error

---

## 6. Constants Agent

**Source**: `src/Constants/Define.php`

**Role**: Centralized definition of all MixQL query language constants. Provides consistent mapping between PHP code and MixQL syntax.

**Responsibilities**:
- Defines MixQL command keywords
- Defines query modifiers and operators
- Ensures consistent query string construction
- Single source of truth for MixQL syntax

**Constant Categories**:

| Category | Examples | Purpose |
|---|---|---|
| **Commands** | `MIXQL_SELECT`, `MIXQL_CREATE_SALT` | Main query commands |
| **Modifiers** | `MIXQL_LIMIT`, `MIXQL_LENGTH` | Query customization |
| **Operators** | `MIXQL_ASHASH`, `MIXQL_STOREAS` | Query syntax elements |
| **Functions** | `MIXQL_SHA`, `MIXQL_SHA256`, `MIXQL_SHA512`, `MIXQL_ENC_GCM`, `MIXQL_DEC_GCM`, `MIXQL_HMAC`, `MIXQL_ARGON2`, `MIXQL_ARGON2_VERIFY` | Hash/transform functions |

**Usage in Query Building**:
```php
// In QueryTypes.php
$this->query = MIXQL_SELECT . $hash . MIXQL_ASHASH;

// In Extensions.php  
$this->query = $this->query . MIXQL_LIMIT . $limit;
```

**Benefits**:
- Easy syntax updates in one location
- Prevents typos in query strings
- Improves code readability
- Enables IDE autocompletion

**Interfaces**:
- Input: None (compile-time definition)
- Output: String constants used throughout codebase

---

## 7. Test Runner Agent

**Source**: `bin/run.php`

**Role**: Command-line test execution framework. Provides colored output, argument parsing, and test orchestration.

**Responsibilities**:
- Displays MixQL branding and startup banner
- Parses command-line arguments and flags
- Loads and executes test command classes
- Provides colored success/error output
- Handles test execution errors gracefully

**Process Flow**:
1. **Argument Parsing**:
   ```php
   $Class = $argv[0] ?? false;  // Command name
   array_shift($argv);  // Remove command name
   ```

2. **Flag Separation**:
   ```php
   foreach ($argv as $arg) {
       if (strpos($arg, '--') === 0){
           $flags[] = $arg;  // --json, --pretty, etc.
       } else {
           $args[] = $arg;   // Positional arguments
       }
   }
   ```

3. **Command Loading**:
   ```php
   $ClassFile = __DIR__ . '/../tests/' . $type . '/' . $Class . '.php';
   include_once $ClassFile;
   $commandClass = new $Class($args,$flags);
   ```

4. **Execution & Output**:
   ```php
   try {
       if ($commandClass->execute()) {
           echo $CmdHelpers->color('SUCCESS','green','ok') . "\n";
       }
   } catch (Exception $e){
       echo $CmdHelpers->color('ERROR:','red','error') . "\n";
   }
   ```

**Features**:
- Color-coded output (success green, error red)
- Flexible flag parsing (`--json`, `--pretty`, `--bind=param1,param2`)
- Error handling with stack trace display
- Brand-consistent interface

**Interfaces**:
- Input: Command-line arguments
- Output: Colored test results, success/error messages

---

## 8. Test Command Agent

**Source**: `tests/commands/Query.php`, `tests/commands/Test.php`

**Role**: Individual test scenarios that verify MixQL functionality. Uses Command Helpers for shared utilities.

**Responsibilities**:
- Implements specific test scenarios
- Uses MixQL instance for query execution
- Parses test options and flags
- Displays formatted test results
- Validates query responses

**Key Methods in Query Test**:
- `execute()`: Main test execution
- `variant($name, $options)`: Routes to specific test case
- `parseOptions($options)`: Parses command-line flags
- `setDefaults($options)`: Applies default test options

**Test Cases** (from `Query.php`):
- `CreateKey`: Key generation tests
- `CreateSalt`: Salt generation tests  
- `CreateStore`: Query storage tests
- `CreateUUID`: UUID generation tests
- `DeleteStore`: Storage deletion tests
- `ListStore`: Storage listing tests
- `Select`: SELECT query tests
- `raw`: Raw query execution

**Flag Support**:
- `--json`: Output as JSON
- `--pretty`: Pretty-print JSON
- `--bind=value1,value2`: Parameter binding
- `--sha`: Apply SHA modifier
- `--uppercase`: Apply uppercase modifier

**Interfaces**:
- Input: Test arguments and flags
- Output: Formatted test results, success/error indicators

---

## 9. Command Helpers Agent

**Source**: `bin/helpers/CommandHelpers.php`

**Role**: Shared utilities for test commands. Provides colorized output and common parsing functions.

**Responsibilities**:
- ANSI color code management for terminal output
- Command-line option parsing utilities
- Default value management for test options
- Consistent output formatting across tests

**Key Methods**:
- `color($text, $color, $type = null)`: Applies color codes to text
- Color types: `ok` (green), `error` (red), `yellow`, `bluebg`, etc.
- Option parsing and default management

**Color System**:
```php
public function color($text, $color, $type = null)
{
    $colors = [
        'ok' => "\033[32m",      // Green
        'error' => "\033[31m",   // Red
        'yellow' => "\033[33m",  // Yellow
        'bluebg' => "\033[44m",  // Blue background
        // ... more colors
    ];
    return $colors[$color] . $text . "\033[0m";
}
```

**Default Management**:
```php
private function setDefaults($options = [])
{
    $optionsSet = $this->defaultOptions;  // ['amount' => 1, 'length' => 16]
    foreach($options as $key => $value) {
        $optionsSet[$key] = $value;
    }
    return $optionsSet;
}
```

**Interfaces**:
- Input: Text strings, option arrays
- Output: Colored text, parsed options, default-applied arrays

---

## 10. Autoloader Agent

**Source**: `autoload.php`

**Role**: Simple class autoloader for the package. Enables easy inclusion of MixQL classes without manual `include_once` statements.

**Responsibilities**:
- Maps class names to file paths
- Provides simple autoloading mechanism
- Enables clean `include_once` usage in main classes

**Implementation**:
```php
// Simple autoloader that includes key classes
// Used by test runner and can be used by applications
```

**Usage Pattern**:
```php
// In consuming code
include_once 'mixql-php/autoload.php';
$mixql = new MixQL();
```

**Interfaces**:
- Input: Class name references
- Output: Included class files

---

## Agent Interaction Diagram

```
                    ┌─────────────────┐
                    │  Constants      │
                    │     Agent       │
                    └────────┬────────┘
                             │ (defines syntax)
                             ▼
┌──────────┐    ┌────────────────────────┐
│  User    │───▶│   MixQL Facade Agent   │
│  Code    │    └───────────┬────────────┘
└──────────┘                │ (delegates)
                            ▼
                ┌────────────────────────┐
                │  Query Types Agent     │
                └───────────┬────────────┘
                            │ (extends)
                            ▼
                ┌────────────────────────┐
                │ Query Extensions Agent │
                └───────────┬────────────┘
                            │ (final query)
                            ▼
                ┌────────────────────────┐
                │    Request Agent       │
                └───────────┬────────────┘
                            │ (delegates)
                            ▼
                ┌────────────────────────┐
                │    Socket Agent        │
                └───────────┬────────────┘
                            │ (TCP)
                            ▼
                    ┌──────────────┐
                    │  MixQL       │
                    │  Server      │
                    └──────────────┘

┌─────────────────────────────────────────┐
│          Testing Subsystem              │
│                                         │
│  ┌──────────┐    ┌──────────────────┐  │
│  │  Test    │───▶│  Test Runner     │  │
│  │  Args    │    │     Agent        │  │
│  └──────────┘    └────────┬─────────┘  │
│                           │ (loads)    │
│                           ▼            │
│                   ┌──────────────────┐  │
│                   │  Test Command    │  │
│                   │     Agent        │  │
│                   └────────┬─────────┘  │
│                           │ (uses)     │
│                           ▼            │
│                   ┌──────────────────┐  │
│                   │ Command Helpers  │  │
│                   │     Agent        │  │
│                   └──────────────────┘  │
└─────────────────────────────────────────┘
```

---

## Design Patterns in Use

### 1. Fluent Interface Pattern
- Used by MixQL Facade, Query Types, and Query Extensions
- Enables method chaining: `$mixql->select()->bind()->execute()`

### 2. Builder Pattern  
- Query Types builds query strings step by step
- Separates construction from execution

### 3. Facade Pattern
- MixQL class provides simple interface to complex subsystem
- Hides socket communication, query building details

### 4. Template Method Pattern
- Request Agent defines `execute()` interface
- Socket Agent provides `send()` implementation

### 5. Strategy Pattern
- Multiple response formats (JSON, array, pretty)
- Different query types via method calls

### 6. Singleton Pattern (implied)
- Constants Agent provides single source of truth
- Shared definitions across all components

---

## Error Handling Strategy

### 1. Socket Level Errors
- Connection failures return `false`
- Error messages echoed to output
- Timeouts handled via `stream_set_timeout()`

### 2. Server Level Errors
- MixQL server returns error strings (`INVALID_INPUT`, etc.)
- Passed through to client unchanged
- Client can check response content

### 3. PHP Level Errors
- Type errors for invalid method parameters
- Standard PHP exceptions
- Test runner catches and displays exceptions

### 4. Test Level Errors
- Color-coded error output
- Exception catching in test runner
- Clear error messages with context

---

## Configuration Management

### Runtime Configuration
```php
// Set via constructor
$mixql = new MixQL(
    ['timeout' => 30],  // Socket timeout
    'localhost',        // Server host  
    7272               // Server port
);
```

### Test Configuration
```php
// Via command-line flags
bash bin/run.php Query --json --pretty --bind=value1,value2

// Default test options
$defaultOptions = ['amount' => 1, 'length' => 16];
```

### Constant Configuration
- Defined in `src/Constants/Define.php`
- Compile-time configuration of MixQL syntax
- Single source of truth for query language

---

## Extension Points for Developers

### 1. Adding New Query Types
Extend `QueryTypes` class:
```php
class CustomQueryTypes extends QueryTypes {
    public function customCommand($param): self {
        $this->query = MIXQL_CUSTOM . $param;
        return $this;
    }
}
```

### 2. Adding Response Formatters  
Extend `Extensions` class:
```php
class CustomExtensions extends Extensions {
    public function xml(): self {
        // Convert response to XML
        return $this;
    }
}
```

### 3. Custom Transport Layer
Replace `RequestSocket`:
```php
class HttpRequest extends RequestSocket {
    public function send($query) {
        // HTTP implementation instead of TCP
    }
}
```

### 4. Enhanced Test Framework
Extend test commands and helpers for custom testing scenarios.

---

## Security Considerations for Agents

### 1. Socket Agent
- No encryption (relies on MixQL server security)
- Timeout protection against hanging connections
- Error disclosure limited to connection issues

### 2. Query Building Agents
- No SQL injection protection (not applicable to MixQL)
- Server validates all query syntax
- Parameter binding via newline separation (not interpolation)

### 3. Test Agents
- No sensitive data in test outputs
- Color codes only for display, not data

### 4. Overall Security Posture
- Client library only, no server responsibilities
- Security depends on MixQL server configuration
- No local data storage or processing

---

## Performance Characteristics

### 1. Connection Management
- New socket per query execution
- No connection pooling (simple design)
- Lightweight socket setup/teardown

### 2. Query Building
- In-memory string concatenation
- Minimal object overhead
- Fluent chaining without intermediate objects

### 3. Response Processing
- Stream reading with 1024-byte buffers
- In-memory response accumulation
- Formatting operations on complete response

### 4. Memory Usage
- Lightweight object structures
- No large buffers or caches
- Response size limited by server output

---

## Testing Strategy

### Unit Test Coverage
- **Query Building**: Verify correct MixQL syntax generation
- **Parameter Binding**: Test multi-line query construction  
- **Response Formatting**: Validate JSON, array, pretty output
- **Error Handling**: Test socket and server error scenarios

### Integration Testing
- **Server Communication**: End-to-end query execution
- **Real Server**: Tests against running MixQL server
- **Command-Line Interface**: Full test runner execution

### Test Types
- **Functional Tests**: `tests/commands/Query.php`
- **Basic Tests**: `tests/commands/Test.php`
- **Interactive Tests**: Via test runner with flags

### Test Execution
```bash
# Run query tests
php bin/run.php Query

# Run basic tests  
php bin/run.php Test

# Test with specific options
php bin/run.php Query --json --pretty --bind=test1,test2
```
# MixQL PHP Package — Architecture

## Overview

The MixQL PHP package is a client library for interacting with the MixQL encryption server. It provides a fluent, object-oriented interface for constructing and executing MixQL queries, handling socket communication, and processing server responses.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    MixQL PHP Client                      │
│                                                         │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────┐  │
│  │   MixQL      │───▶│   Query      │───▶│  Request │  │
│  │   Class      │    │   Types      │    │  Layer   │  │
│  │ (Main Entry) │    │  (Builder)   │    │ (Socket) │  │
│  └──────┬───────┘    └──────┬───────┘    └─────┬────┘  │
│         │                   │                   │       │
│         │                   ▼                   │       │
│         │            ┌──────────────┐           │       │
│         │            │  Extensions  │           │       │
│         │            │  (Modifiers) │           │       │
│         │            └──────────────┘           │       │
│         │                                       │       │
│         └───────────────────────────────────────┘       │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Constants (Query Definitions & Configuration)    │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │  MixQL Server    │
                    │  (TCP on port    │
                    │   7272 default)  │
                    └──────────────────┘
```

## Component Architecture

### 1. Core MixQL Class (`src/MixQL.php`)

**Role**: Main entry point and facade for the library.

**Responsibilities**:
- Initializes connection to MixQL server
- Provides fluent query building interface
- Manages query execution and response handling
- Implements `__toString()` for easy string conversion

**Key Methods**:
- `__construct($options, $host, $port)`: Initializes connection
- `execute()`: Sends query to server and returns self for chaining
- `__toString()`: Returns response as string

**Dependencies**:
- `QueryTypes` (for query building)
- `Request` (for socket communication)

### 2. Query Types Builder (`src/Query/Types.php`)

**Role**: Provides type-safe methods for constructing MixQL queries.

**Responsibilities**:
- Maps PHP method calls to MixQL query language
- Maintains query state during building
- Extends `Extensions` for modifier methods

**Key Methods**:
- `raw(string $query)`: Raw query execution
- `select(string $hash)`: SELECT query builder
- `createSalt()`: CREATE SALT command
- `createKey()`: CREATE KEY command
- `createUUID()`: CREATE UUID command
- `storeList()`: STORE LIST command
- `storeDelete(string $name)`: STORE DELETE command

**Query Building Pattern**:
```php
$mixql->select('SHA1(:input)')->execute();
// Builds: "SELECT SHA1(:input) AS hash"
```

### 3. Query Extensions (`src/Query/Extensions.php`)

**Role**: Provides modifier methods for customizing queries.

**Responsibilities**:
- Adds query modifiers (LIMIT, LENGTH, SHA, etc.)
- Handles parameter binding
- Provides response formatting options

**Key Methods**:
- `amount(int $limit)`: Adds LIMIT clause
- `length(int $length)`: Adds LENGTH clause
- `sha()`: Adds SHA modifier
- `uppercase()`: Adds UPPERCASE modifier
- `store(string $name)`: Adds STORE AS clause
- `bind(array $params)`: Binds parameters to query
- `json()`: Formats response as JSON
- `pretty()`: Pretty-prints JSON response
- `array()`: Converts response to PHP array
- `rawQuery()`: Returns raw query string

**Modifier Chaining Pattern**:
```php
$mixql->createSalt()->amount(5)->length(32)->sha()->execute();
// Builds: "CREATE SALT LIMIT 5 LENGTH 32 SHA"
```

### 4. Request Layer (`src/Send/Request.php`)

**Role**: Handles socket communication with MixQL server.

**Responsibilities**:
- Manages TCP socket connection
- Sends queries and receives responses
- Handles connection errors and timeouts
- Extends `RequestSocket` for actual socket operations

**Key Methods**:
- `__construct(string $host, int $port, array $options)`: Connection setup
- `execute($query)`: Sends query and returns response

### 5. Socket Implementation (`src/Send/Socket/Socket.php`)

**Role**: Low-level TCP socket operations.

**Responsibilities**:
- Establishes socket connection using `fsockopen()`
- Sends query data with proper formatting
- Reads response stream
- Handles socket errors and timeouts

**Key Methods**:
- `send($query)`: Core socket communication method
- Handles query normalization (newline handling)
- Manages stream timeouts
- Ensures proper connection cleanup

### 6. Constants (`src/Constants/Define.php`)

**Role**: Centralized definition of MixQL query language constants.

**Responsibilities**:
- Defines all MixQL keywords and operators
- Provides consistent query language mapping
- Enables easy updates to query syntax

**Key Constants**:
- Query commands: `MIXQL_SELECT`, `MIXQL_CREATE_SALT`, etc.
- Modifiers: `MIXQL_LIMIT`, `MIXQL_LENGTH`, `MIXQL_SHA`, etc.
- Operators: `MIXQL_ASHASH`, `MIXQL_STOREAS`, etc.

## Request Flow

1. **Query Construction**:
   ```php
   $mixql = new MixQL();
   $mixql->select('SHA1(:input)')->bind(['hello'])->execute();
   ```

2. **Query Building**:
   - `MixQL` delegates to `QueryTypes`
   - `QueryTypes` builds query string using constants
   - Modifiers are applied via `Extensions`

3. **Socket Communication**:
   - `Request::execute()` calls `RequestSocket::send()`
   - Socket connects to server (default: localhost:7272)
   - Query is sent with proper newline termination
   - Response is streamed back

4. **Response Processing**:
   - Raw response stored in `MixQL::$res`
   - Can be formatted via `json()`, `pretty()`, `array()` methods
   - Implicit string conversion via `__toString()`

## Error Handling

The library provides basic error handling:

1. **Socket Errors**: Connection failures return `false` and echo error message
2. **Server Errors**: MixQL server returns error strings like `INVALID_INPUT`
3. **PHP Errors**: Standard PHP exceptions for type errors, etc.

## Configuration

### Connection Options
```php
$mixql = new MixQL(
    ['timeout' => 30],  // Socket timeout in seconds
    'localhost',        // Server host
    7272               // Server port
);
```

### Default Values
- Host: `'localhost'`
- Port: `7272`
- Timeout: `30` seconds

## Testing Architecture

### Test Runner (`bin/run.php`)

**Role**: Command-line test execution framework.

**Responsibilities**:
- Loads test command classes
- Parses command-line arguments and flags
- Provides colored output and error handling
- Displays MixQL branding

### Test Commands (`tests/commands/`)

**Role**: Individual test scenarios.

**Structure**:
- `Query.php`: Comprehensive query testing
- `Test.php`: Basic functionality tests

**Test Command Pattern**:
```php
class Query extends CommandHelpers {
    public function execute() {
        // Test logic using MixQL instance
        $result = $this->mixql->createSalt()->execute();
        // Assert and display results
    }
}
```

### Command Helpers (`bin/helpers/CommandHelpers.php`)

**Role**: Shared utilities for test commands.

**Responsibilities**:
- Colorized output formatting
- Command-line argument parsing
- Default option management

## Design Patterns

### 1. Fluent Interface
```php
$mixql->select('SHA1(:input)')
      ->bind(['password123'])
      ->uppercase()
      ->execute()
      ->json()
      ->pretty();
```

### 2. Builder Pattern
- `QueryTypes` builds query strings
- `Extensions` adds modifiers
- Separation of construction and execution

### 3. Facade Pattern
- `MixQL` class provides simple interface
- Hides complexity of socket communication and query building

### 4. Strategy Pattern
- Different query types via method calls
- Response formatting strategies (json, array, pretty)

## Dependencies

### PHP Requirements
- PHP 7.4+ (for typed properties, match expression)
- `fsockopen()` enabled (for TCP sockets)
- `json_encode()`/`json_decode()` (for JSON formatting)

### External Dependencies
- **MixQL Server**: Required TCP server running on specified host:port
- **No external PHP packages**: Pure PHP implementation

## File Structure

```
mixql-php/
├── ARCHITECTURE.md          # This document
├── README.md               # Project overview
├── STRUCTURE.md           # File structure reference
├── autoload.php           # Simple autoloader
├── bin/
│   ├── helpers/
│   │   └── CommandHelpers.php  # Test utilities
│   └── run.php                 # Test runner
├── src/
│   ├── Constants/
│   │   └── Define.php          # Query language constants
│   ├── MixQL.php               # Main class
│   ├── Query/
│   │   ├── Extensions.php      # Query modifiers
│   │   └── Types.php           # Query type builders
│   └── Send/
│       ├── Request.php         # Request handler
│       └── Socket/
│           └── Socket.php      # TCP socket implementation
└── tests/
    └── commands/
        ├── Query.php           # Query tests
        └── Test.php            # Basic tests
```

## Integration with MixQL Ecosystem

### MixQL Server Compatibility
- Uses MixQL query language protocol
- Supports all MixQL server commands
- Compatible with server authentication (when enabled)

### MixQL CLI Parallel
- Similar query building patterns
- Complementary to CLI for programmatic use
- Shared constants and query language

### Deployment Considerations
- Lightweight: No external PHP dependencies
- Simple autoloader: Easy integration
- Flexible configuration: Host/port/timeout options

## Performance Considerations

1. **Connection Pooling**: Each `MixQL` instance maintains one connection
2. **Query Building**: In-memory string construction (minimal overhead)
3. **Socket Reuse**: Connections are established per execution
4. **Memory Usage**: Lightweight object structure

## Security Considerations

1. **Network Security**: Relies on MixQL server's security
2. **No Local Storage**: Doesn't store sensitive data
3. **Input Validation**: Server validates queries
4. **Authentication**: Supports server authentication when enabled

## Extension Points

### Custom Query Types
Extend `QueryTypes` class to add new query methods.

### Response Formatters
Add methods to `Extensions` for custom response formatting.

### Transport Layer
Replace `RequestSocket` with alternative transport (HTTP, etc.).

### Configuration Management
Extend configuration options via `MixQL` constructor.
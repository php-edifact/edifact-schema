# EDIFACT JSON Schema

This repository contains JSON Schema definitions for EDIFACT messages, segments, composite data elements, and data elements, generated from the EDIFACT D95B standard.

## Overview

The schemas are organized by EDIFACT version (currently D95B) and type:

```
edifact-schema/
├── D95B/
│   ├── dataelement/        # Simple data element schemas
│   ├── compositedataelement/  # Composite data element schemas
│   ├── segment/            # Segment schemas
│   └── message/            # Message schemas (e.g., APERAK, INVOIC, etc.)
├── schema.php              # PHP generator script
├── composer.json           # PHP dependencies
└── README.md               # This file
```

## Base URL

All schemas use `https://php-edifact.github.io/edifact-schema/` as the base URL for `$id` and `$ref` references. When hosting on GitHub Pages, these schemas can reference each other using relative paths or the full URL.

## Schema Details

### JSON Schema Draft 2020-12

All schemas follow the [JSON Schema Draft 2020-12](https://json-schema.org/specification-links.html#2020-12) specification.

### Schema Structure

- **Data Elements**: Simple fields with type, maxLength, and optional enum/oneOf constraints
- **Composite Data Elements**: Objects containing multiple data elements
- **Segments**: Objects containing data elements and composite data elements
- **Messages**: Objects containing segments and segment groups

### Example Usage

#### Validating a Segment

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$ref": "https://php-edifact.github.io/edifact-schema/D95B/segment/ADR.edifact.schema.json"
}
```

#### Validating a Data Element with Code List

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$ref": "https://php-edifact.github.io/edifact-schema/D95B/dataelement/1001.edifact.schema.json"
}
```

### Validation Example (Python)

```python
import json
import jsonschema

# Load the schema
with open('D95B/message/APERAK.edifact.schema.json') as f:
    schema = json.load(f)

# Validate a message
message = {
    "UNH": {...},
    "BGM": {...},
    # ... other segments
}

try:
    jsonschema.validate(message, schema)
    print("Valid message!")
except jsonschema.ValidationError as e:
    print(f"Validation error: {e.message}")
```

### Validation Example (JavaScript)

```javascript
const Ajv = require('ajv');
const schema = require('./D95B/message/APERAK.edifact.schema.json');

const ajv = new Ajv({ schemaId: 'auto' });
ajv.addSchema(schema);

const message = { /* your message data */ };
const valid = ajv.validate('https://php-edifact.github.io/edifact-schema/D95B/message/APERAK.edifact.schema.json', message);

if (!valid) {
  console.log('Validation errors:', ajv.errors);
}
```

## Regenerating Schemas

To regenerate the schemas from EDIFACT XML definitions:

```bash
cd edifact-schema
composer install
php schema.php          # Generate D95B (default)
php schema.php D96A    # Generate specific version
php schema.php all     # Generate all versions (D00A-D99B)
```

This will create/update the D95B directory with all schema files.

## Known Limitations

### 1. UNH Segment References

Some message schemas contain references to non-existent UNH component files (e.g., `unh1.edifact.schema.json`, `unh2.edifact.schema.json`). This is because the UNH segment uses semantic names rather than numeric data element IDs in the source XML.

### 2. Composite Data Elements in Segments

Some segment properties reference composite data elements that may not exist in the generated schema files.

## Publishing to GitHub Pages

1. Push this repository to GitHub
2. Go to Repository Settings > Pages
3. Select the `main` branch as the source
4. The schemas will be available at `https://php-edifact.github.io/edifact-schema/`

## EDIFACT Versions

Currently supported:
- **D95B** - UN/EDIFACT Directory D95B (December 1995)

## License

This project is provided as-is for EDIFACT to JSON Schema conversion purposes.

## References

- [JSON Schema Specification](https://json-schema.org/)
- [EDIFACT Standard](https://www.unece.org/tradewelcome/unedifact.html)
- [php-edifact](https://github.com/php-edifact/edifact-mapping) - PHP EDIFACT mapping library

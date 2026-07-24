# µADR-020: Constant-Memory Binary Stream Piping
-----
tags: request http streaming memory
status: accepted

## Context
Loading large binary payloads into standard memory strings exceeds allocation limits.

## Decision
- Request provides pipeRawBody() utilizing stream_copy_to_stream().
- Data is piped directly from php://input to the target file stream without hitting the memory heap.

## Guardrail / Consequences
Processing large binary file uploads as memory strings is prohibited.

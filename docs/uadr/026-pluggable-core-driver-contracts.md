# µADR-026: Pluggable Core Driver Contracts Architecture
-----
tags: #architecture #contracts #decoupling #drivers #solid
status: accepted
context: Coupling framework infrastructure directly to third-party packages limits flexibility and hinders testing.
decision:
  - Extract all infrastructure capabilities behind Contracts (DatabaseDriverInterface, RouterInterface, ViewEngineInterface).
  - Framework core maintains zero dependencies on concrete persistence, routing, or template engines.
consequences:
  - Enables seamless swapping of drivers via Composer packages.
  - Ensures clean dependency inversion across the entire framework ecosystem.

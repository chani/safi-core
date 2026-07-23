# µADR-030: Rejected Built-in Reactive Component Engine in Core
-----
tags: #reactive #htmx #dom-morphing #yagni #architecture
status: rejected
context: Consideration of building a native PHP-driven DOM-morphing engine in Core to handle reactive client-side UI mutations.
reason:
  - Adding JS runtimes and DOM-diffing protocols inside the PHP kernel bloats the framework and breaks KISS principles.
  - Open standards like HTMX solve HTML-over-the-wire natively in the browser without requiring core PHP changes.
accepted_alternative:
  - Controllers return standard lightweight HTML responses or Twig template fragments when handling XHR/HTMX requests.
consequences:
  - Keeps the kernel decoupled from frontend framework paradigms.

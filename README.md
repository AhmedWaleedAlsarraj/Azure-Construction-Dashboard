# Construction Dashboard - Cloud Infrastructure Project

A live site-monitoring dashboard for construction sites, built and deployed on Microsoft Azure, pulling real-time weather and location data via public APIs.

> Note: this was built as university coursework with a fixed module timeline. The Azure VM was decommissioned after the module ended, so there's no live demo but the code, architecture, and security setup are all here.

## What it does

Construction sites need live conditions data weather, location context without someone manually checking multiple sources. This dashboard pulls that together into a single view, backed by a real cloud deployment rather than just running on localhost.

## Architecture

- **Azure VM** running **Ubuntu**, **Apache**, **PHP**, and **MariaDB**
- Integrated **OpenWeather** and **OpenStreetMap** APIs for real-time site data
- Deployed and managed entirely solo — provisioning, configuration, and security all handled end-to-end

## Security

Built with a defense-in-depth approach rather than a single layer of protection:

- Network security groups restricting inbound/outbound traffic
- Role-based access control
- Parameterized queries to prevent SQL injection
- Resource locks to prevent accidental deletion or misconfiguration

## Cost and sustainability

Ran a full cost and sustainability analysis on the deployment right-sizing the VM and optimizing scheduling to stay within budget while achieving an **A+ carbon efficiency rating**. This wasn't an afterthought; infrastructure decisions were made with cost and environmental impact in mind from the start, not just "does it work."

## Tech stack

- **Microsoft Azure** (VM provisioning, networking, security)
- **Linux (Ubuntu)** + **Apache**
- **PHP**
- **MariaDB**
- **OpenWeather API**, **OpenStreetMap API**

## Running it locally

This project expects a LAMP-style stack (Linux, Apache, MySQL/MariaDB, PHP). To run locally:

```bash
git clone https://github.com/AhmedWaleedAlsarraj/Azure-Construction-Dashboard.git
cd Azure-Construction-Dashboard
```

1. Set up a local Apache + PHP + MariaDB environment (e.g. XAMPP or WAMP on Windows)
2. Create a `.env` file based on `.env.example` with your own OpenWeather API key and database credentials
3. Import the database schema (if included) into MariaDB
4. Point Apache at the project folder and run

## Notes

API keys and database credentials are loaded from environment variables see `.env.example` for the expected format. No real credentials are committed to this repo.

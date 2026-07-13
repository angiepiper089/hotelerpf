# Azure Deployment Guide

This project is a server-side PHP application, so it needs a runtime that can execute PHP.
**Azure App Service (Linux, PHP runtime)** is the right target — Azure Static Web Apps only serves
static files plus optional Azure Functions APIs, it cannot run PHP directly. Pair the App Service
with an **Azure SQL Database** for storage.

## 1. Create the Azure SQL Database

1. In the Azure Portal: **Create a resource → SQL Database**.
   - Create a new SQL Server (logical server) if you don't have one; note the **admin username and
     password** you set — you'll need them below.
   - Database name: `hotel_erp`.
   - Pricing tier: the free/serverless "Basic" or "Serverless (General Purpose)" tier is enough
     for a class prototype.
2. Once created, open the database resource → **Networking** → enable
   **"Allow Azure services and resources to access this server"**, and add your own IP under
   firewall rules so you can connect from your laptop to load the schema.
3. Open **Query Editor** (in the database resource) and sign in with the admin credentials.
4. Run the contents of [`database/schema.sql`](../database/schema.sql), then
   [`database/seed.sql`](../database/seed.sql), in that order.

## 2. Create the Azure App Service

1. **Create a resource → Web App**.
   - Publish: **Code**.
   - Runtime stack: **PHP 8.2** (or latest available).
   - Operating System: **Linux**.
   - Pick the same region as your SQL Database to minimise latency.
   - Pricing plan: **Free F1** or **Basic B1** is enough for a prototype.
2. Once created, open the Web App resource → **Configuration → Application settings**, and add:

   | Name | Value |
   |---|---|
   | `DB_HOST` | `<your-server-name>.database.windows.net` |
   | `DB_PORT` | `1433` |
   | `DB_NAME` | `hotel_erp` |
   | `DB_USER` | your SQL admin username |
   | `DB_PASS` | your SQL admin password |

   Save — the app restarts automatically. `config/db.php` reads these via `getenv()`, so no code
   change is needed between local and Azure.
3. The Linux PHP image on App Service does **not** include the `sqlsrv`/`pdo_sqlsrv` extensions by
   default. Add a **Startup Command** under Configuration → General settings so they're installed
   on container start:
   ```
   docker-php-ext-install pdo_sqlsrv 2>/dev/null; apt-get update && apt-get install -y unixodbc unixodbc-dev && pecl install sqlsrv pdo_sqlsrv && docker-php-ext-enable sqlsrv pdo_sqlsrv
   ```
   *(If your group finds extension installation unreliable on the shared App Service image, the
   simpler fallback is to point `DB_HOST`/`DB_NAME`/etc. at an **Azure Database for MySQL**
   instance instead and load `database/schema.mysql.sql` — `config/db.php` already auto-detects
   and falls back to the `pdo_mysql` driver when `pdo_sqlsrv` isn't loaded.)*

## 3. Deploy the Code

### Option A — GitHub Actions (recommended, satisfies the "regular commits" marking criterion)

1. In the Azure Portal, open your Web App → **Deployment Center** → choose **GitHub** as the
   source, authorize, and select this repository/branch. Azure will generate a workflow file and a
   publish profile secret automatically — or use the one already included at
   [`.github/workflows/azure-deploy.yml`](../.github/workflows/azure-deploy.yml).
2. If using the included workflow, add the publish profile as a GitHub secret:
   - Azure Portal → Web App → **Get publish profile** (downloads an XML file).
   - GitHub repo → **Settings → Secrets and variables → Actions → New repository secret**:
     - Name: `AZURE_WEBAPP_PUBLISH_PROFILE`
     - Value: paste the XML file contents.
   - Also update `AZURE_WEBAPP_NAME` in the workflow file to match your Web App's name.
3. Push to `main` — the workflow deploys automatically. Every group member's commits then show up
   both in GitHub history and as an Azure deployment, which is exactly what the rubric checks for.

### Option B — Azure CLI (quick manual deploy)

```bash
az login
az webapp up --name <your-app-name> --resource-group <your-resource-group> --runtime "PHP:8.2"
```

## 4. Verify

- Visit `https://<your-app-name>.azurewebsites.net` — you should see the login page.
- Log in with a demo account (see `README.md`) and confirm the Dashboard loads real data from
  Azure SQL.
- Keep the app running during your presentation — the assignment requires the deployed app to be
  accessible live during grading.
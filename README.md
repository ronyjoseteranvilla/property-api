# Property API
Follow these steps to set up and run the API endpoints.

## Installation Process
### 1. Copy the enviroment file
Laravel uses `.env` for configuration. Copy the example file:
```bash
cp .env.example .env
```
Edit `.env` as needed, especially the database credentials to match your `docker-compose.yml`.
### 2. Start the database container
Start the Docker database service in the background:
```
docker compose up -d
```

### 3. Install PHP dependencies
Laravel uses Composer for dependencies
```
composer install
```

### 4. Generate the application key
```
php artisan key:generate
```

### 5. Run database migrations
Create the database tables
```
php artisan migrate
```

### 6. Start the Laravel development server
```
php artisan serve
```
By default, this runs the API at:
```
http://127.0.0.1:8000
```

### 7. Test the API endpoints
Use toold like Postman, Insomnia or `curl`

#### Register a User
We need the `token` key from this response to set our Bearer token.
```
curl -X POST http://localhost:8000/api/v1/register \
-H "Content-Type: application/json" \
-d '{"name": "user_name","email": "user_email","password": "user_password"}'
```

#### User Login
In cases where you need a new `token`
```
curl -X POST http://localhost:8000/api/v1/login \ 
-H "Content-Type: application/json" \
-d '{"email": "user_email","password": "user_password"}'
```


#### Create a Node
**All the next requests need the token from the Register or Login endpoints**
```
curl -X POST http://localhost:8000/api/v1/nodes \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE" \
-d '{"name":"Acme Corp","type":"Corporation"}'
```

#### Get all child nodes of a given node
```
curl -X GET http://localhost:8000/api/v1/nodes/5/children \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Change the parent node of a given node
```
curl -X POST http://localhost:8000/api/v1//nodes/5/children \
-H "Content-Type: application/json" \
-H "Authorization: Bearer YOUR_TOKEN_HERE" \
-d '{"parent_id": 1}'
```
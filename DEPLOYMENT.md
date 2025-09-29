# 🚀 NicheNest Deployment Guide

This guide covers deploying NicheNest using Docker and Jenkins CI/CD pipeline.

## 📋 Prerequisites

- Docker and Docker Compose installed
- Jenkins server with Docker plugin
- Docker registry (optional, for image storage)
- MySQL database (or use Docker MySQL)

## 🐳 Docker Setup

### Local Development

1. **Clone the repository**

   ```bash
   git clone <your-repo-url>
   cd NicheNest
   ```

2. **Copy environment file**

   ```bash
   cp env.example .env
   ```

3. **Start services**

   ```bash
   docker-compose up -d
   ```

4. **Access the application**
   - Application: <http://localhost:8080>
   - phpMyAdmin: <http://localhost:8081>

### Production Deployment

1. **Set production environment variables**

   ```bash
   export MYSQL_ROOT_PASSWORD=your-secure-password
   export MYSQL_DATABASE=nichenest
   export MYSQL_USER=nichenest
   export MYSQL_PASSWORD=your-secure-password
   ```

2. **Deploy to production**

   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

## 🔧 Jenkins Pipeline Setup

### 1. Jenkins Configuration

1. **Install required plugins**:
   - Docker Pipeline
   - Docker
   - Credentials Binding
   - Git

2. **Configure credentials**:
   - `mysql-root-password`: MySQL root password
   - `mysql-password`: MySQL user password
   - `docker-registry-credentials`: Docker registry credentials (if using)

### 2. Create Jenkins Job

1. **New Item** → **Pipeline**
2. **Pipeline script from SCM** → **Git**
3. **Repository URL**: Your Git repository
4. **Script Path**: `.jenkins/Jenkinsfile`

### 3. Pipeline Stages

The pipeline includes:

- **Checkout**: Get latest code
- **Build**: Create Docker image
- **Test**: Run container tests
- **Security Scan**: Scan for vulnerabilities
- **Push**: Push to registry (optional)
- **Deploy**: Deploy to environment
- **Health Check**: Verify deployment

## 🌍 Environment Variables

### Development (.env)

```env
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=nichenest
MYSQL_USER=nichenest
MYSQL_PASSWORD=nichenest123
```

### Production

```env
MYSQL_ROOT_PASSWORD=your-secure-password
MYSQL_DATABASE=nichenest
MYSQL_USER=nichenest
MYSQL_PASSWORD=your-secure-password
```

## 🔍 Monitoring & Health Checks

### Application Health

- **URL**: <http://localhost:8080>
- **Health Check**: `curl -f http://localhost:8080/`

### Database Health

```bash
docker exec nichenest-mysql mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} -e "SELECT 1" ${MYSQL_DATABASE}
```

### Container Status

```bash
docker ps
docker logs nichenest-app
docker logs nichenest-mysql
```

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check MySQL container is running
   - Verify environment variables
   - Check network connectivity

2. **Application Not Accessible**
   - Check if container is running
   - Verify port mapping
   - Check Apache logs

3. **Permission Issues**
   - Check file permissions
   - Ensure uploads directory is writable

### Logs

```bash
# Application logs
docker logs nichenest-app

# Database logs
docker logs nichenest-mysql

# All services
docker-compose logs
```

## 🔒 Security Considerations

1. **Change default passwords**
2. **Use environment variables for secrets**
3. **Enable HTTPS in production**
4. **Regular security updates**
5. **Database backups**

## 📊 Performance Optimization

1. **Enable PHP OPcache**
2. **Use Redis for sessions**
3. **Implement CDN for static files**
4. **Database query optimization**
5. **Container resource limits**

## 🚨 Backup & Recovery

### Database Backup

```bash
docker exec nichenest-mysql mysqldump -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} > backup.sql
```

### Restore Database

```bash
docker exec -i nichenest-mysql mysql -u${MYSQL_USER} -p${MYSQL_PASSWORD} ${MYSQL_DATABASE} < backup.sql
```

## 📈 Scaling

### Horizontal Scaling

- Use load balancer
- Multiple app containers
- Shared database
- Session storage (Redis)

### Vertical Scaling

- Increase container resources
- Database optimization
- Caching layers

## 🔄 CI/CD Best Practices

1. **Automated testing**
2. **Security scanning**
3. **Blue-green deployments**
4. **Rollback strategies**
5. **Monitoring and alerting**

## 📞 Support

For issues and questions:

- Check logs first
- Review this documentation
- Create GitHub issue
- Contact development team

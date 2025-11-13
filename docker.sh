#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if .env file exists
check_env_file() {
    if [ ! -f .env ]; then
        print_warning ".env file not found. Creating from .env.example..."
        if [ -f .env.example ]; then
            cp .env.example .env
            print_status "Please edit .env file with your database credentials"
        else
            print_error ".env.example not found. Please create .env file manually"
            return 1
        fi
    fi
    return 0
}

# Give execute permissions to shell scripts
permissions() {
    print_status "Setting execute permissions for shell scripts..."
    chmod +x ./docker.sh
    chmod +x ./docker/init_db.sh
    chmod +x ./docker/php/entrypoint.sh
    chmod +x ./import_academic_history.sh
    chmod +x ./test_import.sh
    chmod +x ./test_import_docker.sh
    print_status "Execute permissions set successfully"
}

# Setup the project
setup() {
    print_status "Setting up Simulación Curricular project..."
    
    # Check for .env file
    if ! check_env_file; then
        print_error "Please create .env file first"
        exit 1
    fi
    
    # Give execute permissions to all shell scripts
    print_status "Setting execute permissions for shell scripts..."
    chmod +x ./docker.sh
    chmod +x ./docker/init_db.sh
    chmod +x ./docker/php/entrypoint.sh
    chmod +x ./import_academic_history.sh
    chmod +x ./test_import.sh
    chmod +x ./test_import_docker.sh
    
    # Build and start containers
    print_status "Building and starting containers..."
    docker-compose up -d --build
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 15
    
    # Run migrations
    print_status "Running migrations..."
    docker-compose exec app php artisan migrate --force
    
    # Run specific seeders (excluding student data)
    print_status "Running seeders..."
    docker-compose exec app php artisan db:seed --class=SubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=PrerequisitesSeeder --force
    docker-compose exec app php artisan db:seed --class=LevelingSubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=ElectiveSubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=SubjectAliasSeeder --force
    
    print_status "Setup complete! Application is running at http://localhost:8080"
}

# Start the application
start() {
    print_status "Starting containers..."
    docker-compose up -d
}

# Stop the application
stop() {
    print_status "Stopping containers..."
    docker-compose down
}

# Restart the application
restart() {
    print_status "Restarting containers..."
    docker-compose down
    docker-compose up -d
}

# Show logs
logs() {
    if [ -n "$1" ]; then
        docker-compose logs -f "$1"
    else
        docker-compose logs -f
    fi
}

# Access application container
shell() {
    docker-compose exec app bash
}

# Access database container
db_shell() {
    docker-compose exec db psql -U ${DB_USERNAME} -d ${DB_DATABASE}
}

# Run artisan commands
artisan() {
    docker-compose exec app php artisan "$@"
}

# Run composer commands
composer() {
    docker-compose exec app composer "$@"
}

# Check database status
db_status() {
    print_status "Checking database status..."
    echo "Subjects count:"
    docker-compose exec app php artisan tinker --execute="echo 'Total subjects: ' . \App\Models\Subject::count();"
    echo "Prerequisites count:"
    docker-compose exec app php artisan tinker --execute="echo 'Total prerequisites: ' . DB::table('subject_prerequisites')->count();"
    echo "Students count:"
    docker-compose exec app php artisan tinker --execute="echo 'Total students: ' . \App\Models\Student::count();"
    echo "Student-subject records:"
    docker-compose exec app php artisan tinker --execute="echo 'Total academic records: ' . DB::table('student_subject')->count();"
}

# Run seeders (excluding student data seeders)
seed() {
    print_status "Running seeders (excluding student data)..."
    docker-compose exec app php artisan db:seed --class=SubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=PrerequisitesSeeder --force
    docker-compose exec app php artisan db:seed --class=LevelingSubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=ElectiveSubjectSeeder --force
    docker-compose exec app php artisan db:seed --class=SubjectAliasSeeder --force
    print_status "Seeders completed successfully!"
}

# Migrate fresh and seed
migrate_fresh() {
    print_warning "This will DROP ALL TABLES and recreate them. Are you sure? (y/n)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_status "Running migrate:fresh..."
        docker-compose exec app php artisan migrate:fresh --force
        print_status "Running seeders..."
        seed
        print_status "Database reset complete!"
    else
        print_status "Operation cancelled."
    fi
}

# Force reinstall composer dependencies
composer-reinstall() {
    print_status "Forcing Composer dependencies reinstallation..."
    docker-compose exec app rm -rf vendor
    docker-compose exec app composer install --optimize-autoloader
    print_status "Composer dependencies reinstalled successfully"
}

# Run npm commands
npm() {
    docker-compose exec app npm "$@"
}

# Show help
help() {
    echo "Simulación Curricular - Docker Management Script"
    echo ""
    echo "Usage: ./docker.sh [command]"
    echo ""
    echo "Commands:"
    echo "  setup           - Initial setup (build, migrate, seed - excludes student data)"
    echo "  permissions     - Set execute permissions for shell scripts"
    echo "  start           - Start containers"
    echo "  stop            - Stop containers"
    echo "  restart         - Restart containers"
    echo "  logs            - Show container logs (optional: specify service)"
    echo "  shell           - Access application container"
    echo "  db-shell        - Access database container"
    echo "  db-status       - Check database tables status"
    echo "  seed            - Run seeders (excludes DepartmentUserSeeder, StudentSeeder, StudentCurrentSubjectSeeder)"
    echo "  migrate-fresh   - Drop all tables, migrate and seed (prompts for confirmation)"
    echo "  import-csv      - Import student data from CSV file"
    echo "  artisan         - Run Laravel artisan commands"
    echo "  composer        - Run composer commands"
    echo "  composer-reinstall - Force reinstall composer dependencies"
    echo "  npm             - Run npm commands"
    echo "  help            - Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./docker.sh setup"
    echo "  ./docker.sh seed"
    echo "  ./docker.sh migrate-fresh"
    echo "  ./docker.sh import-csv"
    echo "  ./docker.sh logs app"
    echo "  ./docker.sh artisan migrate"
    echo "  ./docker.sh composer install"
    echo "  ./docker.sh composer-reinstall"
    echo "  ./docker.sh npm run dev"
}

# Main script logic
case "$1" in
    setup)
        setup
        ;;
    permissions)
        permissions
        ;;
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    logs)
        shift
        logs "$@"
        ;;
    shell)
        shell
        ;;
    db-shell)
        db_shell
        ;;
    db-status)
        db_status
        ;;
    seed)
        seed
        ;;
    migrate-fresh)
        migrate_fresh
        ;;
    import-csv)
        import_csv
        ;;
    artisan)
        shift
        artisan "$@"
        ;;
    composer)
        shift
        composer "$@"
        ;;
    composer-reinstall)
        composer-reinstall
        ;;
    npm)
        shift
        npm "$@"
        ;;
    help|*)
        help
        ;;
esac

#!/bin/bash

#MySQL Database Backup Script v1.0 
#Set .env path (Change this based on your project structure)
PROJECT_PATH="/mnt/c/gestock_saas" 
echo "PATH: $PROJECT_PATH"

#We collect the DB credentials from the .env file

#Specify the values that are needed to connet to the DB

DB_CONNECTION=$(grep '^DB_CONNECTION=' "$PROJECT_PATH/.env" | cut -d '=' -f2)
DB_HOST=$(grep '^DB_HOST' "$PROJECT_PATH/.env" | cut -d '=' -f2)
DB_PORT=$(grep '^DB_PORT' "$PROJECT_PATH/.env" | cut -d '=' -f2)
DB_DATABASE=$(grep '^DB_DATABASE' "$PROJECT_PATH/.env" | cut -d '=' -f2)
DB_USERNAME=$(grep '^DB_USERNAME' "$PROJECT_PATH/.env" | cut -d '=' -f2)
DB_PASSWORD=$(grep '^DB_PASSWORD' "$PROJECT_PATH/.env" | cut -d '=' -f2)

echo "retrieving DB credentials from  .env files..."

echo "DB_CONNECTION: $DB_CONNECTION"
echo "DB_HOST: $DB_HOST"
echo "DB_PORT: $DB_PORT"
echo "DB_DATABASE: $DB_DATABASE"
echo "DB_USERNAME: $DB_USERNAME"
echo "DB_PASSWORD: $DB_PASSWORD"


#Set BU Directory

BU_DIR="$PROJECT_PATH/backup" #Change this based on your project structure

if [ ! -d "$BU_DIR" ]
then
    mkdir -p "$BU_DIR"
    echo "Backup directory created at: $BU_DIR"
else
    echo "Backup directory already exists at: $BU_DIR"
fi

#create GZ file for MySQL Dump

TIME_STAMP=$(date +%y-%m-%d)

#Set Prefix for BU File Name
Prefix_File_Name="gestock_BU_" #Change this based on your Project Structure

File_Name="$Prefix_File_Name$TIME_STAMP.sql.gz"

echo "TIME_STAMP: $TIME_STAMP"
echo "Prefix_File_Name: $Prefix_File_Name"

echo "File_Name: $File_Name"

BU_File="$BU_DIR/$File_Name"

if [ ! -f "$BU_File" ]
then
    touch "$BU_File"
    echo "Backup file created at: $BU_File"
else
    echo "Backup file already exists at: $BU_File"
fi

#Generate MySQL Dump, compress it using gzip and save it to the BU_DIR/BU_File.sql.gz 
echo "Starting MySQL database backup..."
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$BU_File"

if [ $? -eq 0 ]; then
    echo "MySQL database backup completed successfully."
    echo "Backup file saved at: $BU_File"
else
    echo "Error occurred during MySQL database backup."
fi

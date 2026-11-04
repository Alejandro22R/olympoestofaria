SELECT 'CREATE DATABASE olympo_test' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'olympo_test')\gexec

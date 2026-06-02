CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DO $$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'senac') THEN
    CREATE ROLE senac LOGIN PASSWORD 'senac';
  END IF;
END
$$;

CREATE DATABASE development_db
  OWNER senac;

CREATE DATABASE testing_db
  OWNER senac;

CREATE DATABASE production_db
  OWNER senac;
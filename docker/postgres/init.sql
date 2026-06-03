CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

DO $$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'yehior') THEN
    CREATE ROLE yehior LOGIN PASSWORD 'yehior';
  END IF;
END
$$;

CREATE DATABASE development_db
  OWNER yehior;

CREATE DATABASE testing_db
  OWNER yehior;

CREATE DATABASE production_db
  OWNER yehior;
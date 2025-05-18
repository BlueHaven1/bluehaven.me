-- This SQL script should be run in the Supabase SQL Editor

-- Create a profiles table that extends the auth.users table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS public.profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    first_name TEXT,
    last_name TEXT,
    full_name TEXT,
    avatar_url TEXT,
    role TEXT DEFAULT 'user',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    PRIMARY KEY (id)
);

-- Create a function to handle new user signups
CREATE OR REPLACE FUNCTION public.handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    -- Insert a row into public.profiles
    INSERT INTO public.profiles (id, first_name, last_name, full_name, avatar_url, role)
    VALUES (
        NEW.id,
        NEW.raw_user_meta_data->>'first_name',
        NEW.raw_user_meta_data->>'last_name',
        NEW.raw_user_meta_data->>'full_name',
        NEW.raw_user_meta_data->>'avatar_url',
        COALESCE(NEW.raw_user_meta_data->>'role', 'user')
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create a trigger to call the function when a new user signs up (if it doesn't exist)
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_new_user();

-- Create a function to update profiles when user metadata changes
CREATE OR REPLACE FUNCTION public.handle_user_update()
RETURNS TRIGGER AS $$
BEGIN
    -- Update the profiles table when user metadata changes
    UPDATE public.profiles
    SET
        first_name = NEW.raw_user_meta_data->>'first_name',
        last_name = NEW.raw_user_meta_data->>'last_name',
        full_name = NEW.raw_user_meta_data->>'full_name',
        avatar_url = NEW.raw_user_meta_data->>'avatar_url',
        role = COALESCE(NEW.raw_user_meta_data->>'role', role),
        updated_at = now()
    WHERE id = NEW.id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create a trigger to call the function when a user is updated (if it doesn't exist)
DROP TRIGGER IF EXISTS on_auth_user_updated ON auth.users;
CREATE TRIGGER on_auth_user_updated
    AFTER UPDATE ON auth.users
    FOR EACH ROW EXECUTE FUNCTION public.handle_user_update();

-- Set up Row Level Security (RLS) for the profiles table
ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;

-- Create policies for the profiles table
-- Allow users to view their own profile
DROP POLICY IF EXISTS "Users can view their own profile" ON public.profiles;
CREATE POLICY "Users can view their own profile"
    ON public.profiles
    FOR SELECT
    USING (auth.uid() = id);

-- Allow users to update their own profile
DROP POLICY IF EXISTS "Users can update their own profile" ON public.profiles;
CREATE POLICY "Users can update their own profile"
    ON public.profiles
    FOR UPDATE
    USING (auth.uid() = id);

-- Create a purchases table to track user purchases (if it doesn't exist)
CREATE TABLE IF NOT EXISTS public.purchases (
    id UUID DEFAULT uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    product_id TEXT NOT NULL,
    product_name TEXT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency TEXT DEFAULT 'USD',
    status TEXT DEFAULT 'completed',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Set up Row Level Security for the purchases table
ALTER TABLE public.purchases ENABLE ROW LEVEL SECURITY;

-- Create policies for the purchases table
-- Allow users to view their own purchases
DROP POLICY IF EXISTS "Users can view their own purchases" ON public.purchases;
CREATE POLICY "Users can view their own purchases"
    ON public.purchases
    FOR SELECT
    USING (auth.uid() = user_id);

-- Allow authenticated users to insert purchases
DROP POLICY IF EXISTS "Authenticated users can insert purchases" ON public.purchases;
CREATE POLICY "Authenticated users can insert purchases"
    ON public.purchases
    FOR INSERT
    WITH CHECK (auth.uid() = user_id);

-- Create a products table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS public.products (
    id TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image_url TEXT,
    category TEXT,
    is_featured BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Allow anyone to view products
ALTER TABLE public.products ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view products" ON public.products;
CREATE POLICY "Anyone can view products"
    ON public.products
    FOR SELECT
    USING (true);

-- Insert some sample products (if they don't exist)
DO $$
BEGIN
    -- Staff Panel
    IF NOT EXISTS (SELECT 1 FROM public.products WHERE id = 'staff-panel') THEN
        INSERT INTO public.products (id, name, description, price, category, is_featured)
        VALUES ('staff-panel', 'Staff Panel', 'A comprehensive staff management system with permissions, time tracking, and performance analytics.', 45.00, 'scripts', true);
    END IF;

    -- Community Portal
    IF NOT EXISTS (SELECT 1 FROM public.products WHERE id = 'community-portal') THEN
        INSERT INTO public.products (id, name, description, price, category, is_featured)
        VALUES ('community-portal', 'Community Portal', 'A unique community site for your server to stand out. Fully configurable to your likings with extensive features.', 35.00, 'websites', true);
    END IF;

    -- Forms System
    IF NOT EXISTS (SELECT 1 FROM public.products WHERE id = 'forms-system') THEN
        INSERT INTO public.products (id, name, description, price, category, is_featured)
        VALUES ('forms-system', 'Forms System', 'Application Portal for your community. A simple & enjoyable experience to make your community standout.', 20.00, 'scripts', true);
    END IF;
END
$$;

-- Note: This is just a placeholder. In a real application, you would need to create a user through
-- the Supabase Auth API and then update their role to superadmin.
-- For testing purposes, you can manually update an existing user's role to 'superadmin' using:
-- UPDATE public.profiles SET role = 'superadmin' WHERE id = 'your-user-id';

-- Create a function to check if a user has purchased a product
CREATE OR REPLACE FUNCTION public.has_purchased(product_id TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    RETURN EXISTS (
        SELECT 1 FROM public.purchases
        WHERE user_id = auth.uid()
        AND product_id = $1
        AND status = 'completed'
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create a function to set a user's role
CREATE OR REPLACE FUNCTION public.set_user_role(user_id UUID, new_role TEXT)
RETURNS BOOLEAN AS $$
DECLARE
    current_user_role TEXT;
BEGIN
    -- Check if the current user is an admin or superadmin
    SELECT role INTO current_user_role FROM public.profiles WHERE id = auth.uid();

    IF current_user_role NOT IN ('admin', 'superadmin') THEN
        RAISE EXCEPTION 'Only admins can change user roles';
        RETURN FALSE;
    END IF;

    -- Superadmins can set any role, admins can only set 'user' or 'admin' roles
    IF current_user_role = 'admin' AND new_role = 'superadmin' THEN
        RAISE EXCEPTION 'Admins cannot set superadmin role';
        RETURN FALSE;
    END IF;

    -- Update the user's role
    UPDATE public.profiles
    SET role = new_role
    WHERE id = user_id;

    RETURN TRUE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

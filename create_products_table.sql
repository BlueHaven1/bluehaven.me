-- Create products table if it doesn't exist
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

-- Drop existing policy if it exists
DROP POLICY IF EXISTS "Anyone can view products" ON public.products;

-- Create policy to allow anyone to view products
CREATE POLICY "Anyone can view products" 
    ON public.products 
    FOR SELECT 
    USING (true);

-- Drop existing policy if it exists
DROP POLICY IF EXISTS "Admins can modify products" ON public.products;

-- Create policy to allow admins to modify products
CREATE POLICY "Admins can modify products" 
    ON public.products 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

-- Insert sample products if they don't exist
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

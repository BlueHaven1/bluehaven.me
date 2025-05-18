-- Drop existing policies
DROP POLICY IF EXISTS "Anyone can view products" ON public.products;
DROP POLICY IF EXISTS "Admins can modify products" ON public.products;
DROP POLICY IF EXISTS "Admins can insert products" ON public.products;
DROP POLICY IF EXISTS "Admins can update products" ON public.products;
DROP POLICY IF EXISTS "Admins can delete products" ON public.products;

-- Create policy to allow anyone to view products
CREATE POLICY "Anyone can view products" 
    ON public.products 
    FOR SELECT 
    USING (true);

-- Create policy to allow admins to insert products
CREATE POLICY "Admins can insert products" 
    ON public.products 
    FOR INSERT 
    WITH CHECK (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

-- Create policy to allow admins to update products
CREATE POLICY "Admins can update products" 
    ON public.products 
    FOR UPDATE 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

-- Create policy to allow admins to delete products
CREATE POLICY "Admins can delete products" 
    ON public.products 
    FOR DELETE 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

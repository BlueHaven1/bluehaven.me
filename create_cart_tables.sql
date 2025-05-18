-- Create cart_items table
CREATE TABLE IF NOT EXISTS public.cart_items (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    product_id TEXT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    UNIQUE(user_id, product_id)
);

-- Create purchases table to track completed orders
CREATE TABLE IF NOT EXISTS public.purchases (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    total_amount DECIMAL(10, 2) NOT NULL,
    status TEXT NOT NULL DEFAULT 'completed',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Create purchase_items table to track items in each purchase
CREATE TABLE IF NOT EXISTS public.purchase_items (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    purchase_id UUID NOT NULL REFERENCES public.purchases(id) ON DELETE CASCADE,
    product_id TEXT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL DEFAULT 1,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Set up RLS policies for cart_items
ALTER TABLE public.cart_items ENABLE ROW LEVEL SECURITY;

-- Users can only see their own cart items
CREATE POLICY "Users can view their own cart items" 
    ON public.cart_items 
    FOR SELECT 
    USING (auth.uid() = user_id);

-- Users can only insert their own cart items
CREATE POLICY "Users can insert their own cart items" 
    ON public.cart_items 
    FOR INSERT 
    WITH CHECK (auth.uid() = user_id);

-- Users can only update their own cart items
CREATE POLICY "Users can update their own cart items" 
    ON public.cart_items 
    FOR UPDATE 
    USING (auth.uid() = user_id);

-- Users can only delete their own cart items
CREATE POLICY "Users can delete their own cart items" 
    ON public.cart_items 
    FOR DELETE 
    USING (auth.uid() = user_id);

-- Set up RLS policies for purchases
ALTER TABLE public.purchases ENABLE ROW LEVEL SECURITY;

-- Users can only see their own purchases
CREATE POLICY "Users can view their own purchases" 
    ON public.purchases 
    FOR SELECT 
    USING (auth.uid() = user_id);

-- Users can only insert their own purchases
CREATE POLICY "Users can insert their own purchases" 
    ON public.purchases 
    FOR INSERT 
    WITH CHECK (auth.uid() = user_id);

-- Set up RLS policies for purchase_items
ALTER TABLE public.purchase_items ENABLE ROW LEVEL SECURITY;

-- Users can view purchase items for their own purchases
CREATE POLICY "Users can view their own purchase items" 
    ON public.purchase_items 
    FOR SELECT 
    USING (
        purchase_id IN (
            SELECT id FROM public.purchases WHERE user_id = auth.uid()
        )
    );

-- Create function to check if uuid_generate_v4 extension exists
CREATE OR REPLACE FUNCTION create_uuid_extension()
RETURNS VOID AS $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_extension WHERE extname = 'uuid-ossp'
    ) THEN
        CREATE EXTENSION "uuid-ossp";
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Call the function to ensure uuid-ossp extension is created
SELECT create_uuid_extension();

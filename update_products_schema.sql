-- Add subscription-related fields to products table
ALTER TABLE public.products 
ADD COLUMN IF NOT EXISTS is_subscription BOOLEAN DEFAULT false,
ADD COLUMN IF NOT EXISTS subscription_price DECIMAL(10, 2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS subscription_interval TEXT DEFAULT 'month',
ADD COLUMN IF NOT EXISTS subscription_description TEXT;

-- Create subscriptions table to track active subscriptions
CREATE TABLE IF NOT EXISTS public.subscriptions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    product_id TEXT NOT NULL REFERENCES public.products(id) ON DELETE CASCADE,
    status TEXT NOT NULL DEFAULT 'active', -- active, cancelled, expired
    start_date TIMESTAMP WITH TIME ZONE DEFAULT now(),
    end_date TIMESTAMP WITH TIME ZONE,
    next_billing_date TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Create subscription_payments table to track payment history
CREATE TABLE IF NOT EXISTS public.subscription_payments (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    subscription_id UUID NOT NULL REFERENCES public.subscriptions(id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL,
    status TEXT NOT NULL DEFAULT 'completed', -- completed, failed, refunded
    payment_date TIMESTAMP WITH TIME ZONE DEFAULT now(),
    next_payment_date TIMESTAMP WITH TIME ZONE
);

-- Set up RLS policies for subscriptions
ALTER TABLE public.subscriptions ENABLE ROW LEVEL SECURITY;

-- Users can only see their own subscriptions
CREATE POLICY "Users can view their own subscriptions" 
    ON public.subscriptions 
    FOR SELECT 
    USING (auth.uid() = user_id);

-- Users can only insert their own subscriptions
CREATE POLICY "Users can insert their own subscriptions" 
    ON public.subscriptions 
    FOR INSERT 
    WITH CHECK (auth.uid() = user_id);

-- Users can only update their own subscriptions
CREATE POLICY "Users can update their own subscriptions" 
    ON public.subscriptions 
    FOR UPDATE 
    USING (auth.uid() = user_id);

-- Set up RLS policies for subscription_payments
ALTER TABLE public.subscription_payments ENABLE ROW LEVEL SECURITY;

-- Users can only see payments for their own subscriptions
CREATE POLICY "Users can view their own subscription payments" 
    ON public.subscription_payments 
    FOR SELECT 
    USING (
        subscription_id IN (
            SELECT id FROM public.subscriptions WHERE user_id = auth.uid()
        )
    );

-- Admins can manage all subscriptions
CREATE POLICY "Admins can manage all subscriptions" 
    ON public.subscriptions 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

-- Admins can manage all subscription payments
CREATE POLICY "Admins can manage all subscription payments" 
    ON public.subscription_payments 
    USING (
        EXISTS (
            SELECT 1 FROM public.profiles
            WHERE id = auth.uid()
            AND role IN ('admin', 'superadmin')
        )
    );

-- Add sample subscription product if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM public.products WHERE id = 'premium-support') THEN
        INSERT INTO public.products (
            id, 
            name, 
            description, 
            price, 
            category, 
            is_featured,
            is_active,
            is_subscription,
            subscription_price,
            subscription_interval,
            subscription_description
        )
        VALUES (
            'premium-support', 
            'Premium Support Plan', 
            'Get priority support for all your FiveM server needs with our premium support plan.', 
            0.00, 
            'services', 
            true,
            true,
            true,
            29.99,
            'month',
            'Monthly subscription for premium support services. Includes 24/7 priority support, monthly server health checks, and performance optimization.'
        );
    END IF;
END
$$;

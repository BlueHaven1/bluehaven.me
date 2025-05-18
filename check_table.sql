-- Create a function to check if the products table exists
CREATE OR REPLACE FUNCTION check_products_table()
RETURNS json AS $$
DECLARE
    result json;
BEGIN
    SELECT json_build_object(
        'table_exists', EXISTS (
            SELECT FROM information_schema.tables 
            WHERE table_schema = 'public' 
            AND table_name = 'products'
        ),
        'column_info', (
            SELECT json_agg(json_build_object(
                'column_name', column_name,
                'data_type', data_type,
                'is_nullable', is_nullable
            ))
            FROM information_schema.columns
            WHERE table_schema = 'public' 
            AND table_name = 'products'
        )
    ) INTO result;
    
    RETURN result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

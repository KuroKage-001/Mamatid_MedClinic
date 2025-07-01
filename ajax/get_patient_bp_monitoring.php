$query = "SELECT 
        id,
        name,
        date,
        address,
        sex,
        bp,
        alcohol,
        smoke,
        obese,
        cp_number
    FROM general_bp_monitoring 
    WHERE name = :patient_name 
    ORDER BY date DESC"; 
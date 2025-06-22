-- Add a unique constraint to the appointments table to prevent multiple bookings for the same time slot
-- First, make sure there are no duplicate appointments for the same schedule and time
DELETE a1 FROM appointments a1
INNER JOIN appointments a2 
WHERE a1.id > a2.id 
  AND a1.schedule_id = a2.schedule_id 
  AND a1.appointment_time = a2.appointment_time
  AND a1.status != 'cancelled' 
  AND a2.status != 'cancelled';

-- Add a unique constraint to prevent multiple active appointments for the same time slot
ALTER TABLE appointments
ADD CONSTRAINT unique_active_appointment
UNIQUE KEY (schedule_id, appointment_time, status);

-- Update triggers to enforce one appointment per slot

-- Drop existing triggers
DROP TRIGGER IF EXISTS after_appointment_insert;
DROP TRIGGER IF EXISTS after_appointment_update;
DROP TRIGGER IF EXISTS after_appointment_delete;

-- Create new triggers
DELIMITER $$

-- After insert trigger
CREATE TRIGGER after_appointment_insert AFTER INSERT ON appointments FOR EACH ROW
BEGIN
    DECLARE slot_id INT;
    
    -- Check if slot exists
    SELECT id INTO slot_id FROM appointment_slots 
    WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time
    LIMIT 1;
    
    IF slot_id IS NULL THEN
        -- Create slot if it doesn't exist
        INSERT INTO appointment_slots (schedule_id, slot_time, is_booked, appointment_id)
        VALUES (NEW.schedule_id, NEW.appointment_time, 1, NEW.id);
    ELSE
        -- Update existing slot
        UPDATE appointment_slots 
        SET is_booked = 1, appointment_id = NEW.id
        WHERE id = slot_id;
    END IF;
END$$

-- After update trigger
CREATE TRIGGER after_appointment_update AFTER UPDATE ON appointments FOR EACH ROW
BEGIN
    -- If status changed to cancelled, update the slot
    IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
        UPDATE appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
    END IF;
    
    -- If time slot changed, update both old and new slots
    IF NEW.appointment_time != OLD.appointment_time THEN
        -- Update old slot
        UPDATE appointment_slots
        SET is_booked = 0, appointment_id = NULL
        WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
        
        -- Update new slot
        UPDATE appointment_slots
        SET is_booked = 1, appointment_id = NEW.id
        WHERE schedule_id = NEW.schedule_id AND slot_time = NEW.appointment_time;
    END IF;
END$$

-- After delete trigger
CREATE TRIGGER after_appointment_delete AFTER DELETE ON appointments FOR EACH ROW
BEGIN
    -- Update the slot when an appointment is deleted
    UPDATE appointment_slots
    SET is_booked = 0, appointment_id = NULL
    WHERE schedule_id = OLD.schedule_id AND slot_time = OLD.appointment_time;
END$$

DELIMITER ;

-- Update all doctor_schedules to have max_patients = 1
UPDATE doctor_schedules SET max_patients = 1;

-- Update all appointment_slots to reflect correct booking status
UPDATE appointment_slots AS slots
LEFT JOIN (
    SELECT schedule_id, appointment_time, COUNT(*) as count
    FROM appointments
    WHERE status != 'cancelled'
    GROUP BY schedule_id, appointment_time
) AS appts ON slots.schedule_id = appts.schedule_id AND slots.slot_time = appts.appointment_time
SET slots.is_booked = IF(appts.count > 0, 1, 0); 
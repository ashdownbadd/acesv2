<?php

class MemberRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM members WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($data)
    {
        $cleanBalance = (float)str_replace(',', '', $data['balance']);

        $sql = "UPDATE members SET 
                prefix = ?, first_name = ?, middle_name = ?, last_name = ?, suffix = ?, 
                birthdate = ?, death_date = ?, civil_status = ?, address = ?, 
                membership_type = ?, status = ?, email = ?, 
                phone_number = ?, phone_number_2 = ?, 
                telephone_number = ?, telephone_number_2 = ?, 
                remarks = ?, balance = ? 
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['prefix'],
            $data['first_name'],
            $data['middle_name'],
            $data['last_name'],
            $data['suffix'],
            $data['birthdate'] ?: null,
            $data['death_date'] ?: null,
            $data['civil_status'],
            $data['address'],
            $data['membership_type'],
            $data['status'],
            $data['email'],
            $data['phone_number'],
            $data['phone_number_2'],
            $data['telephone_number'],
            $data['telephone_number_2'],
            $data['remarks'],
            $cleanBalance,
            (int)$data['member_id']
        ]);
    }
}

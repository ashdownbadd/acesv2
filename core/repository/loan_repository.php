<?php
// core/repository/loan_repository.php

class LoanRepository
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Fetch all loans associated with a specific member
    public function findByMemberId($memberId)
    {
        $stmt = $this->db->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY date_released DESC");
        $stmt->execute([(int)$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a new loan record
    public function create($data)
    {
        $sql = "INSERT INTO loans (member_id, loan_type, principal, interest_rate, terms, monthly_amortization, collateral, soa_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['member_id'],
            $data['loan_type'],
            $data['principal_amount'],
            $data['interest_rate'],
            $data['terms'],
            $data['monthly_amortization'],
            $data['collateral'],
            $data['soa_status']
        ]);
    }
}

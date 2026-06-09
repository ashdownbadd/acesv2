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
        //  AYOS NA: Binago mula 'date_released' tungo sa 'start_date' para hindi na mag-error ang database sorting
        $stmt = $this->db->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY start_date DESC");
        $stmt->execute([(int)$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add a new loan record
    public function create($data)
    {
        //  AYOS NA: Idinagdag na rin natin ang start_date sa insert query para mai-save ang piniling petsa
        $sql = "INSERT INTO loans (member_id, loan_type, principal, interest_rate, terms, collateral, soa_status, start_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['member_id'] ?? null,
            $data['loan_type'] ?? null,
            //  AYOS NA: Tiniyak na katugma ito ng JSON payload key ('principal') mula sa JS fetch block mo
            $data['principal'] ?? 0,
            $data['interest_rate'] ?? 0,
            $data['terms'] ?? 0,
            $data['collateral'] ?? null,
            $data['soa_status'] ?? 'Pending',
            //  AYOS NA: Map ang 'start_date' string diretso sa bagong db entry
            $data['start_date'] ?? null
        ]);
    }
}

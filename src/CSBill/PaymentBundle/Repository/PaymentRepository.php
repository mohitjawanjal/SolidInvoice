<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Repository;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use Doctrine\ORM\EntityRepository;

class PaymentRepository extends EntityRepository
{

    /**
     * Returns an array of all the payments for an invoice
     *
     * @param Invoice $invoice
     * @param string  $orderField
     * @param string  $sort
     *
     * @return array
     */
    public function getPaymentsForInvoice(Invoice $invoice, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.invoice = :invoice')
            ->setParameter('invoice', $invoice);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * Returns an array of all the payments for a client
     *
     * @param Client  $client
     * @param string  $orderField
     * @param string  $sort
     *
     * @return array
     */
    public function getPaymentsForClient(Client $client, $orderField = null, $sort = 'DESC')
    {
        $queryBuilder = $this->getPaymentQueryBuilder($orderField, $sort);

        $queryBuilder
            ->where('p.client = :client')
            ->setParameter('client', $client);

        $query = $queryBuilder->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @param string $orderField
     * @param string $sort
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getPaymentQueryBuilder($orderField = null, $sort = 'DESC')
    {
        if (null === $orderField) {
            $orderField = 'p.created';
        }

        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.id',
            'p.amount',
            'p.currency',
            'p.created',
            'p.completed',
            'i.id as invoice',
            'm.name as method',
            's.name as status',
            's.label as status_label',
            'p.message'
        )
            ->join('p.method', 'm')
            ->join('p.status', 's')
            ->join('p.invoice', 'i')
            ->orderBy($orderField, $sort);

        return $queryBuilder;
    }

    /**
     * Gets the most recent created payments
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentPayments($limit = 5)
    {
        $qb = $this->getPaymentQueryBuilder();

        $qb->addSelect(
            'c.name as client',
            'c.id as client_id'
        )
            ->join('p.client', 'c')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * @return array
     */
    public function getPaymentsList()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.amount',
            'p.created'
        )
            ->join('p.method', 'm')
            ->join('p.status', 's')
            ->where('p.created >= :date')
            ->setParameter('date', new \DateTime('-1 Month'))
            ->groupBy('p.created')
            ->orderBy('p.created', 'ASC');

        $query = $queryBuilder->getQuery();

        $payments = array();

        foreach ($query->getArrayResult() as $result) {
            $date = $result['created']->format('Y-m-d');
            if (!isset($payments[$date])) {
                $payments[$date] = 0;
            }
            $payments[$date] += $result['amount'];
        }

        $results = array();

        foreach ($payments as $date => $amount) {
            $results[] = array(strtotime($date) * 1000, $amount);
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getPaymentsByMonth()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder->select(
            'p.amount',
            'p.created'
        )
            ->join('p.method', 'm')
            ->join('p.status', 's')
            ->where('p.created >= :date')
            ->setParameter('date', new \DateTime('-1 Year'))
            ->groupBy('p.created')
            ->orderBy('p.created', 'ASC');

        $query = $queryBuilder->getQuery();

        $payments = array();

        foreach ($query->getArrayResult() as $result) {
            $date = $result['created']->format('F Y');
            if (!isset($payments[$date])) {
                $payments[$date] = 0;
            }
            $payments[$date] += $result['amount'];
        }

        return $payments;
    }
}

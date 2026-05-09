<?php

declare(strict_types=1);

namespace XrechnungKit\Mapper\Bookings;

/**
 * The SPI a downstream consumer (e.g. Locaboo) implements to plug their
 * booking domain model into the bookings mapper. The mapper itself does not
 * know about your tables, your row shapes, or your IDs; it works against
 * this adapter.
 *
 * This interface is the load-bearing contract that lets the mapper-bookings
 * generic logic stay framework-agnostic and DB-free. Locaboo will ship a
 * LocabooBookingShapeAdapter inside its own repo to consume mapper-bookings
 * the same way any other downstream user would.
 *
 * Methods are intentionally returning typed value objects from the kit's
 * Mapping namespace; the adapter is the boundary where the domain shape
 * meets the kit's typed graph.
 */
interface BookingShapeAdapter
{
    public function bookingId(): string;

    public function invoiceNumber(): string;

    public function issueDate(): \DateTimeImmutable;

    public function dueDate(): ?\DateTimeImmutable;

    public function currency(): string;

    /** @return list<BookingLine> One element per chargeable item on the booking. */
    public function lines(): array;

    public function billingPeriodStart(): ?\DateTimeImmutable;

    public function billingPeriodEnd(): ?\DateTimeImmutable;

    /** @return non-empty-string */
    public function leitwegIdOrEmpty(): string;
}

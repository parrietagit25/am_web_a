export default function SkeletonCard() {
  return (
    <div
      style={{
        background: '#fff',
        borderRadius: 16,
        overflow: 'hidden',
        boxShadow: '0 1px 3px rgba(0,0,0,.06)',
      }}
      aria-hidden="true"
    >
      <div className="r-vehicle-inner">
        {/* Image panel — matches r-vehicle-image-wrap dimensions */}
        <div
          className="r-vehicle-image-wrap"
          style={{
            background: 'linear-gradient(135deg, #f3f4f6, #e9eaec)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            padding: 20,
            position: 'relative',
            overflow: 'hidden',
          }}
        >
          {/* Shimmer overlay spanning full image panel */}
          <div
            className="skeleton"
            style={{
              position: 'absolute',
              inset: 0,
              borderRadius: 0,
              zIndex: 1,
            }}
          />
          {/* Car silhouette ghost to match real image proportions */}
          <div
            style={{
              width: 200,
              height: 120,
              borderRadius: 12,
              background: 'rgba(0,0,0,.04)',
              zIndex: 0,
            }}
          />
        </div>

        {/* Info panel — matches real VehicleCard info column */}
        <div
          style={{
            flex: 1,
            padding: '24px 28px',
            minWidth: 280,
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'space-between',
          }}
        >
          {/* Top: name + badges + specs */}
          <div>
            {/* Vehicle name line */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, marginBottom: 8 }}>
              <div className="skeleton" style={{ width: '52%', height: 22 }} />
              <div className="skeleton" style={{ width: '14%', height: 16 }} />
            </div>

            {/* Category badge + optional km badge */}
            <div style={{ display: 'flex', gap: 8, marginBottom: 20 }}>
              <div className="skeleton" style={{ width: 90, height: 22, borderRadius: 20 }} />
              <div className="skeleton" style={{ width: 100, height: 22, borderRadius: 20 }} />
            </div>

            {/* Specs grid — 4 items matching auto-fill minmax(130px,1fr) */}
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))',
                gap: '10px 16px',
              }}
            >
              {[...Array(5)].map((_, i) => (
                <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <div className="skeleton" style={{ width: 15, height: 15, borderRadius: 4, flexShrink: 0 }} />
                  <div className="skeleton" style={{ flex: 1, height: 14 }} />
                </div>
              ))}
            </div>
          </div>

          {/* Bottom: price block + buttons — matches borderTop separator */}
          <div
            style={{
              display: 'flex',
              alignItems: 'flex-end',
              justifyContent: 'space-between',
              flexWrap: 'wrap',
              gap: 16,
              marginTop: 20,
              paddingTop: 16,
              borderTop: '1px solid var(--gray-100)',
            }}
          >
            {/* Price column */}
            <div>
              <div className="skeleton" style={{ width: 80, height: 11, marginBottom: 8 }} />
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 14 }}>
                {/* Web price */}
                <div>
                  <div className="skeleton" style={{ width: 72, height: 12, marginBottom: 6, borderRadius: 4 }} />
                  <div className="skeleton" style={{ width: 110, height: 30 }} />
                </div>
                {/* Counter price (crossed out) */}
                <div>
                  <div className="skeleton" style={{ width: 50, height: 11, marginBottom: 4 }} />
                  <div className="skeleton" style={{ width: 80, height: 16 }} />
                </div>
              </div>
              {/* Mandatory charges inline strip */}
              <div style={{ display: 'flex', gap: 8, marginTop: 10 }}>
                <div className="skeleton" style={{ width: 130, height: 13, borderRadius: 4 }} />
                <div className="skeleton" style={{ width: 100, height: 13, borderRadius: 4 }} />
              </div>
            </div>

            {/* Buttons */}
            <div style={{ display: 'flex', gap: 10 }}>
              <div className="skeleton" style={{ width: 108, height: 40, borderRadius: 10 }} />
              <div className="skeleton" style={{ width: 108, height: 40, borderRadius: 10 }} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

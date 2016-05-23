/**
 * QueryObjectsResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class QueryObjectsResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Property[] columns;

    private java.lang.String[][] rows;

    private com.woodwing.enterprise.interfaces.services.wfl.Property[] childColumns;

    private com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] childRows;

    private com.woodwing.enterprise.interfaces.services.wfl.Property[] componentColumns;

    private com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] componentRows;

    private org.apache.axis.types.UnsignedInt firstEntry;

    private org.apache.axis.types.UnsignedInt listedEntries;

    private org.apache.axis.types.UnsignedInt totalEntries;

    private java.lang.String updateID;

    private com.woodwing.enterprise.interfaces.services.wfl.Facet[] facets;

    private com.woodwing.enterprise.interfaces.services.wfl.Feature[] searchFeatures;

    public QueryObjectsResponse() {
    }

    public QueryObjectsResponse(
           com.woodwing.enterprise.interfaces.services.wfl.Property[] columns,
           java.lang.String[][] rows,
           com.woodwing.enterprise.interfaces.services.wfl.Property[] childColumns,
           com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] childRows,
           com.woodwing.enterprise.interfaces.services.wfl.Property[] componentColumns,
           com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] componentRows,
           org.apache.axis.types.UnsignedInt firstEntry,
           org.apache.axis.types.UnsignedInt listedEntries,
           org.apache.axis.types.UnsignedInt totalEntries,
           java.lang.String updateID,
           com.woodwing.enterprise.interfaces.services.wfl.Facet[] facets,
           com.woodwing.enterprise.interfaces.services.wfl.Feature[] searchFeatures) {
           this.columns = columns;
           this.rows = rows;
           this.childColumns = childColumns;
           this.childRows = childRows;
           this.componentColumns = componentColumns;
           this.componentRows = componentRows;
           this.firstEntry = firstEntry;
           this.listedEntries = listedEntries;
           this.totalEntries = totalEntries;
           this.updateID = updateID;
           this.facets = facets;
           this.searchFeatures = searchFeatures;
    }


    /**
     * Gets the columns value for this QueryObjectsResponse.
     * 
     * @return columns
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Property[] getColumns() {
        return columns;
    }


    /**
     * Sets the columns value for this QueryObjectsResponse.
     * 
     * @param columns
     */
    public void setColumns(com.woodwing.enterprise.interfaces.services.wfl.Property[] columns) {
        this.columns = columns;
    }


    /**
     * Gets the rows value for this QueryObjectsResponse.
     * 
     * @return rows
     */
    public java.lang.String[][] getRows() {
        return rows;
    }


    /**
     * Sets the rows value for this QueryObjectsResponse.
     * 
     * @param rows
     */
    public void setRows(java.lang.String[][] rows) {
        this.rows = rows;
    }


    /**
     * Gets the childColumns value for this QueryObjectsResponse.
     * 
     * @return childColumns
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Property[] getChildColumns() {
        return childColumns;
    }


    /**
     * Sets the childColumns value for this QueryObjectsResponse.
     * 
     * @param childColumns
     */
    public void setChildColumns(com.woodwing.enterprise.interfaces.services.wfl.Property[] childColumns) {
        this.childColumns = childColumns;
    }


    /**
     * Gets the childRows value for this QueryObjectsResponse.
     * 
     * @return childRows
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] getChildRows() {
        return childRows;
    }


    /**
     * Sets the childRows value for this QueryObjectsResponse.
     * 
     * @param childRows
     */
    public void setChildRows(com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] childRows) {
        this.childRows = childRows;
    }


    /**
     * Gets the componentColumns value for this QueryObjectsResponse.
     * 
     * @return componentColumns
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Property[] getComponentColumns() {
        return componentColumns;
    }


    /**
     * Sets the componentColumns value for this QueryObjectsResponse.
     * 
     * @param componentColumns
     */
    public void setComponentColumns(com.woodwing.enterprise.interfaces.services.wfl.Property[] componentColumns) {
        this.componentColumns = componentColumns;
    }


    /**
     * Gets the componentRows value for this QueryObjectsResponse.
     * 
     * @return componentRows
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] getComponentRows() {
        return componentRows;
    }


    /**
     * Sets the componentRows value for this QueryObjectsResponse.
     * 
     * @param componentRows
     */
    public void setComponentRows(com.woodwing.enterprise.interfaces.services.wfl.ChildRow[] componentRows) {
        this.componentRows = componentRows;
    }


    /**
     * Gets the firstEntry value for this QueryObjectsResponse.
     * 
     * @return firstEntry
     */
    public org.apache.axis.types.UnsignedInt getFirstEntry() {
        return firstEntry;
    }


    /**
     * Sets the firstEntry value for this QueryObjectsResponse.
     * 
     * @param firstEntry
     */
    public void setFirstEntry(org.apache.axis.types.UnsignedInt firstEntry) {
        this.firstEntry = firstEntry;
    }


    /**
     * Gets the listedEntries value for this QueryObjectsResponse.
     * 
     * @return listedEntries
     */
    public org.apache.axis.types.UnsignedInt getListedEntries() {
        return listedEntries;
    }


    /**
     * Sets the listedEntries value for this QueryObjectsResponse.
     * 
     * @param listedEntries
     */
    public void setListedEntries(org.apache.axis.types.UnsignedInt listedEntries) {
        this.listedEntries = listedEntries;
    }


    /**
     * Gets the totalEntries value for this QueryObjectsResponse.
     * 
     * @return totalEntries
     */
    public org.apache.axis.types.UnsignedInt getTotalEntries() {
        return totalEntries;
    }


    /**
     * Sets the totalEntries value for this QueryObjectsResponse.
     * 
     * @param totalEntries
     */
    public void setTotalEntries(org.apache.axis.types.UnsignedInt totalEntries) {
        this.totalEntries = totalEntries;
    }


    /**
     * Gets the updateID value for this QueryObjectsResponse.
     * 
     * @return updateID
     */
    public java.lang.String getUpdateID() {
        return updateID;
    }


    /**
     * Sets the updateID value for this QueryObjectsResponse.
     * 
     * @param updateID
     */
    public void setUpdateID(java.lang.String updateID) {
        this.updateID = updateID;
    }


    /**
     * Gets the facets value for this QueryObjectsResponse.
     * 
     * @return facets
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Facet[] getFacets() {
        return facets;
    }


    /**
     * Sets the facets value for this QueryObjectsResponse.
     * 
     * @param facets
     */
    public void setFacets(com.woodwing.enterprise.interfaces.services.wfl.Facet[] facets) {
        this.facets = facets;
    }


    /**
     * Gets the searchFeatures value for this QueryObjectsResponse.
     * 
     * @return searchFeatures
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Feature[] getSearchFeatures() {
        return searchFeatures;
    }


    /**
     * Sets the searchFeatures value for this QueryObjectsResponse.
     * 
     * @param searchFeatures
     */
    public void setSearchFeatures(com.woodwing.enterprise.interfaces.services.wfl.Feature[] searchFeatures) {
        this.searchFeatures = searchFeatures;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof QueryObjectsResponse)) return false;
        QueryObjectsResponse other = (QueryObjectsResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.columns==null && other.getColumns()==null) || 
             (this.columns!=null &&
              java.util.Arrays.equals(this.columns, other.getColumns()))) &&
            ((this.rows==null && other.getRows()==null) || 
             (this.rows!=null &&
              java.util.Arrays.equals(this.rows, other.getRows()))) &&
            ((this.childColumns==null && other.getChildColumns()==null) || 
             (this.childColumns!=null &&
              java.util.Arrays.equals(this.childColumns, other.getChildColumns()))) &&
            ((this.childRows==null && other.getChildRows()==null) || 
             (this.childRows!=null &&
              java.util.Arrays.equals(this.childRows, other.getChildRows()))) &&
            ((this.componentColumns==null && other.getComponentColumns()==null) || 
             (this.componentColumns!=null &&
              java.util.Arrays.equals(this.componentColumns, other.getComponentColumns()))) &&
            ((this.componentRows==null && other.getComponentRows()==null) || 
             (this.componentRows!=null &&
              java.util.Arrays.equals(this.componentRows, other.getComponentRows()))) &&
            ((this.firstEntry==null && other.getFirstEntry()==null) || 
             (this.firstEntry!=null &&
              this.firstEntry.equals(other.getFirstEntry()))) &&
            ((this.listedEntries==null && other.getListedEntries()==null) || 
             (this.listedEntries!=null &&
              this.listedEntries.equals(other.getListedEntries()))) &&
            ((this.totalEntries==null && other.getTotalEntries()==null) || 
             (this.totalEntries!=null &&
              this.totalEntries.equals(other.getTotalEntries()))) &&
            ((this.updateID==null && other.getUpdateID()==null) || 
             (this.updateID!=null &&
              this.updateID.equals(other.getUpdateID()))) &&
            ((this.facets==null && other.getFacets()==null) || 
             (this.facets!=null &&
              java.util.Arrays.equals(this.facets, other.getFacets()))) &&
            ((this.searchFeatures==null && other.getSearchFeatures()==null) || 
             (this.searchFeatures!=null &&
              java.util.Arrays.equals(this.searchFeatures, other.getSearchFeatures())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getColumns() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getColumns());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getColumns(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRows() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRows());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRows(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getChildColumns() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getChildColumns());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getChildColumns(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getChildRows() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getChildRows());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getChildRows(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getComponentColumns() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getComponentColumns());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getComponentColumns(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getComponentRows() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getComponentRows());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getComponentRows(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFirstEntry() != null) {
            _hashCode += getFirstEntry().hashCode();
        }
        if (getListedEntries() != null) {
            _hashCode += getListedEntries().hashCode();
        }
        if (getTotalEntries() != null) {
            _hashCode += getTotalEntries().hashCode();
        }
        if (getUpdateID() != null) {
            _hashCode += getUpdateID().hashCode();
        }
        if (getFacets() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getFacets());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getFacets(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getSearchFeatures() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getSearchFeatures());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getSearchFeatures(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(QueryObjectsResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">QueryObjectsResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("columns");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Columns"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Property"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Property"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rows");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Rows"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Row"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Row"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("childColumns");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ChildColumns"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Property"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Property"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("childRows");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ChildRows"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ChildRow"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ChildRow"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("componentColumns");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ComponentColumns"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Property"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Property"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("componentRows");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ComponentRows"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ChildRow"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ChildRow"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("firstEntry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FirstEntry"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("listedEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ListedEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("totalEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "TotalEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("updateID");
        elemField.setXmlName(new javax.xml.namespace.QName("", "UpdateID"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("facets");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Facets"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Facet"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Facet"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("searchFeatures");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SearchFeatures"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Feature"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Feature"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}

/**
 * NamedQueryType0.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class NamedQueryType0  implements java.io.Serializable {
    private java.lang.String ticket;

    private java.lang.String query;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params;

    private org.apache.axis.types.UnsignedInt firstEntry;

    private org.apache.axis.types.UnsignedInt maxEntries;

    private java.lang.Boolean hierarchical;

    private com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order;

    public NamedQueryType0() {
    }

    public NamedQueryType0(
           java.lang.String ticket,
           java.lang.String query,
           com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params,
           org.apache.axis.types.UnsignedInt firstEntry,
           org.apache.axis.types.UnsignedInt maxEntries,
           java.lang.Boolean hierarchical,
           com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order) {
           this.ticket = ticket;
           this.query = query;
           this.params = params;
           this.firstEntry = firstEntry;
           this.maxEntries = maxEntries;
           this.hierarchical = hierarchical;
           this.order = order;
    }


    /**
     * Gets the ticket value for this NamedQueryType0.
     * 
     * @return ticket
     */
    public java.lang.String getTicket() {
        return ticket;
    }


    /**
     * Sets the ticket value for this NamedQueryType0.
     * 
     * @param ticket
     */
    public void setTicket(java.lang.String ticket) {
        this.ticket = ticket;
    }


    /**
     * Gets the query value for this NamedQueryType0.
     * 
     * @return query
     */
    public java.lang.String getQuery() {
        return query;
    }


    /**
     * Sets the query value for this NamedQueryType0.
     * 
     * @param query
     */
    public void setQuery(java.lang.String query) {
        this.query = query;
    }


    /**
     * Gets the params value for this NamedQueryType0.
     * 
     * @return params
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] getParams() {
        return params;
    }


    /**
     * Sets the params value for this NamedQueryType0.
     * 
     * @param params
     */
    public void setParams(com.woodwing.enterprise.interfaces.services.wfl.QueryParam[] params) {
        this.params = params;
    }


    /**
     * Gets the firstEntry value for this NamedQueryType0.
     * 
     * @return firstEntry
     */
    public org.apache.axis.types.UnsignedInt getFirstEntry() {
        return firstEntry;
    }


    /**
     * Sets the firstEntry value for this NamedQueryType0.
     * 
     * @param firstEntry
     */
    public void setFirstEntry(org.apache.axis.types.UnsignedInt firstEntry) {
        this.firstEntry = firstEntry;
    }


    /**
     * Gets the maxEntries value for this NamedQueryType0.
     * 
     * @return maxEntries
     */
    public org.apache.axis.types.UnsignedInt getMaxEntries() {
        return maxEntries;
    }


    /**
     * Sets the maxEntries value for this NamedQueryType0.
     * 
     * @param maxEntries
     */
    public void setMaxEntries(org.apache.axis.types.UnsignedInt maxEntries) {
        this.maxEntries = maxEntries;
    }


    /**
     * Gets the hierarchical value for this NamedQueryType0.
     * 
     * @return hierarchical
     */
    public java.lang.Boolean getHierarchical() {
        return hierarchical;
    }


    /**
     * Sets the hierarchical value for this NamedQueryType0.
     * 
     * @param hierarchical
     */
    public void setHierarchical(java.lang.Boolean hierarchical) {
        this.hierarchical = hierarchical;
    }


    /**
     * Gets the order value for this NamedQueryType0.
     * 
     * @return order
     */
    public com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] getOrder() {
        return order;
    }


    /**
     * Sets the order value for this NamedQueryType0.
     * 
     * @param order
     */
    public void setOrder(com.woodwing.enterprise.interfaces.services.wfl.QueryOrder[] order) {
        this.order = order;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof NamedQueryType0)) return false;
        NamedQueryType0 other = (NamedQueryType0) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.ticket==null && other.getTicket()==null) || 
             (this.ticket!=null &&
              this.ticket.equals(other.getTicket()))) &&
            ((this.query==null && other.getQuery()==null) || 
             (this.query!=null &&
              this.query.equals(other.getQuery()))) &&
            ((this.params==null && other.getParams()==null) || 
             (this.params!=null &&
              java.util.Arrays.equals(this.params, other.getParams()))) &&
            ((this.firstEntry==null && other.getFirstEntry()==null) || 
             (this.firstEntry!=null &&
              this.firstEntry.equals(other.getFirstEntry()))) &&
            ((this.maxEntries==null && other.getMaxEntries()==null) || 
             (this.maxEntries!=null &&
              this.maxEntries.equals(other.getMaxEntries()))) &&
            ((this.hierarchical==null && other.getHierarchical()==null) || 
             (this.hierarchical!=null &&
              this.hierarchical.equals(other.getHierarchical()))) &&
            ((this.order==null && other.getOrder()==null) || 
             (this.order!=null &&
              java.util.Arrays.equals(this.order, other.getOrder())));
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
        if (getTicket() != null) {
            _hashCode += getTicket().hashCode();
        }
        if (getQuery() != null) {
            _hashCode += getQuery().hashCode();
        }
        if (getParams() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getParams());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getParams(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getFirstEntry() != null) {
            _hashCode += getFirstEntry().hashCode();
        }
        if (getMaxEntries() != null) {
            _hashCode += getMaxEntries().hashCode();
        }
        if (getHierarchical() != null) {
            _hashCode += getHierarchical().hashCode();
        }
        if (getOrder() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getOrder());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getOrder(), i);
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
        new org.apache.axis.description.TypeDesc(NamedQueryType0.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">NamedQuery"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("ticket");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Ticket"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("query");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Query"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("params");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Params"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryParam"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryParam"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("firstEntry");
        elemField.setXmlName(new javax.xml.namespace.QName("", "FirstEntry"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("maxEntries");
        elemField.setXmlName(new javax.xml.namespace.QName("", "MaxEntries"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "unsignedInt"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("hierarchical");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Hierarchical"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "boolean"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("order");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Order"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "QueryOrder"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "QueryOrder"));
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
